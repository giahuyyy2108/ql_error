using System;
using System.Diagnostics;
using System.IO;
using System.Runtime.InteropServices;
using System.Text;
using System.Text.RegularExpressions;
using System.Threading;

internal static class WatcherLauncher
{
    private const string MutexName = @"Local\QlErrorXmlWatcherLauncher";
    private const int StdOutputHandle = -11;
    private const uint EnableVirtualTerminalProcessing = 0x0004;
    private static volatile bool monitorConsoleLayout;

    [DllImport("kernel32.dll")]
    private static extern IntPtr GetStdHandle(int standardHandle);
    [DllImport("kernel32.dll")]
    private static extern bool GetConsoleMode(IntPtr consoleHandle, out uint mode);
    [DllImport("kernel32.dll")]
    private static extern bool SetConsoleMode(IntPtr consoleHandle, uint mode);

    private static int Main(string[] args)
    {
        try { Console.OutputEncoding = Encoding.UTF8; } catch { }
        string projectRoot = AppDomain.CurrentDomain.BaseDirectory.TrimEnd(Path.DirectorySeparatorChar);
        string configPath = Path.Combine(projectRoot, "conf", "config.php");
        string phpPath = ReadConfiguredPath(configPath, "_XML_WATCHER_PHP_PATH_", projectRoot, Path.Combine(Path.GetPathRoot(projectRoot), "xampp", "php", "php.exe"));
        string watcherPath = ReadConfiguredPath(configPath, "_XML_WATCHER_SCRIPT_PATH_", projectRoot, Path.Combine(projectRoot, "scripts", "watch_xml.php"));
        string runtimePath = ReadConfiguredPath(configPath, "_XML_WATCHER_RUNTIME_PATH_", projectRoot, Path.Combine(projectRoot, "storage", "watcher"));
        string progressPath = Path.Combine(runtimePath, ".xml_import_progress.json");
        string turnPath = Path.Combine(runtimePath, ".xml_worker_turn");
        string layoutPath = Path.Combine(runtimePath, ".xml_import_progress_top");
        string logPath = Path.Combine(projectRoot, "storage", "watcher", "logs", DateTime.Now.ToString("yyyyMMdd"), "watcher.launcher.log");
        if (!File.Exists(phpPath)) return Fail(logPath, "PHP executable was not found: " + phpPath);
        if (!File.Exists(watcherPath)) return Fail(logPath, "Watcher script was not found: " + watcherPath);
        if (args.Length > 0 && args[0] == "--check") { Console.WriteLine("WatcherLauncher: OK"); return 0; }

        bool ownsMutex;
        using (var mutex = new Mutex(true, MutexName, out ownsMutex))
        {
            if (!ownsMutex) { Console.WriteLine("WatcherLauncher is already running."); return 0; }
            Console.Title = "QL Error - XML Watcher";
            ResetProgressState(progressPath, turnPath);
            bool consoleProgressEnabled = EnableConsoleProgress();
            if (consoleProgressEnabled)
            {
                Environment.SetEnvironmentVariable("XML_WATCHER_PROGRESS_LAYOUT_PATH", layoutPath);
                DrawProgressPanel(layoutPath);
                StartConsoleLayoutMonitor(layoutPath);
            }
            Console.WriteLine("XML watcher is running. Close this console to stop it.");
            var watchers = new Process[2];
            try
            {
                for (int index = 0; index < watchers.Length; index++)
                {
                    var startInfo = new ProcessStartInfo { FileName = phpPath, Arguments = "\"" + watcherPath + "\" --worker-id=" + (index + 1), WorkingDirectory = projectRoot, UseShellExecute = false, CreateNoWindow = false };
                    watchers[index] = Process.Start(startInfo);
                    if (watchers[index] == null) return Fail(logPath, "Could not start worker " + (index + 1) + ".");
                }
                for (int index = 0; index < watchers.Length; index++)
                {
                    watchers[index].WaitForExit();
                    File.AppendAllText(logPath, DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + " Worker " + (index + 1) + " stopped, exit code " + watchers[index].ExitCode + Environment.NewLine);
                    watchers[index].Dispose();
                }
                return 0;
            }
            catch (Exception exception) { return Fail(logPath, exception.ToString()); }
            finally { if (consoleProgressEnabled) StopConsoleLayoutMonitor(); }
        }
    }

    private static string ReadConfiguredPath(string configPath, string constantName, string projectRoot, string fallback)
    {
        try
        {
            string config = File.ReadAllText(configPath);
            string pattern = "define\\s*\\(\\s*['\\\"]" + Regex.Escape(constantName) + "['\\\"]\\s*,\\s*['\\\"](?<path>[^'\\\"]+)['\\\"]";
            Match match = Regex.Match(config, pattern, RegexOptions.IgnoreCase);
            if (!match.Success) return fallback;
            string configuredPath = match.Groups["path"].Value.Replace('/', Path.DirectorySeparatorChar);
            return Path.IsPathRooted(configuredPath) ? configuredPath : Path.GetFullPath(Path.Combine(projectRoot, configuredPath));
        }
        catch { return fallback; }
    }

    private static void ResetProgressState(string progressPath, string turnPath)
    {
        Directory.CreateDirectory(Path.GetDirectoryName(progressPath));
        File.WriteAllText(progressPath, "{\"items\":{},\"workers\":{}}");
        File.WriteAllText(turnPath, "1");
    }

    private static bool EnableConsoleProgress()
    {
        IntPtr output = GetStdHandle(StdOutputHandle);
        uint mode;
        if (output == IntPtr.Zero || output == new IntPtr(-1) || !GetConsoleMode(output, out mode) || !SetConsoleMode(output, mode | EnableVirtualTerminalProcessing)) return false;
        Environment.SetEnvironmentVariable("XML_WATCHER_ANSI", "1");
        return true;
    }

    private static void DrawProgressPanel(string layoutPath)
    {
        int[] layout = UpdateConsoleLayout(layoutPath);
        Console.Write("\x1B[" + layout[0] + ";" + layout[1] + "H");
        Console.WriteLine("+------------------------------------------------------------------------------+");
        Console.WriteLine("| [WORKER:1] Waiting for file...                                              |");
        Console.WriteLine("| [WORKER:2] Waiting for file...                                              |");
        Console.WriteLine("+------------------------------------------------------------------------------+");
    }

    private static int[] UpdateConsoleLayout(string layoutPath)
    {
        int panelTop = 1;
        int panelLeft = Math.Max(1, ((Console.WindowWidth - 80) / 2) + 1);
        int logTop = panelTop + 4;
        Environment.SetEnvironmentVariable("XML_WATCHER_PROGRESS_TOP", panelTop.ToString());
        File.WriteAllText(layoutPath, panelTop + "," + panelLeft);
        // Reserve the centered panel; logs may scroll only below it.
        Console.Write("\x1B[" + logTop + ";r\x1B[" + logTop + ";1H");
        return new[] { panelTop, panelLeft };
    }

    private static void StartConsoleLayoutMonitor(string layoutPath)
    {
        monitorConsoleLayout = true;
        var thread = new Thread(delegate()
        {
            int previousHeight = -1;
            int previousWidth = -1;
            while (monitorConsoleLayout)
            {
                try
                {
                    int currentHeight = Console.WindowHeight;
                    int currentWidth = Console.WindowWidth;
                    if (currentHeight != previousHeight || currentWidth != previousWidth)
                    {
                        previousHeight = currentHeight;
                        previousWidth = currentWidth;
                        UpdateConsoleLayout(layoutPath);
                    }
                }
                catch { }
                Thread.Sleep(250);
            }
        });
        thread.IsBackground = true;
        thread.Start();
    }

    private static void StopConsoleLayoutMonitor()
    {
        monitorConsoleLayout = false;
    }

    private static int Fail(string logPath, string message)
    {
        try { Directory.CreateDirectory(Path.GetDirectoryName(logPath)); File.AppendAllText(logPath, DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + " " + message + Environment.NewLine); } catch { }
        Console.Error.WriteLine(message);
        return 1;
    }
}
