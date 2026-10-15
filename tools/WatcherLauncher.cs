using System;
using System.Diagnostics;
using System.IO;
using System.Threading;

internal static class WatcherLauncher
{
    private const string MutexName = @"Local\QlErrorXmlWatcherLauncher";

    private static int Main(string[] args)
    {
        string projectRoot = AppDomain.CurrentDomain.BaseDirectory.TrimEnd(Path.DirectorySeparatorChar);
        string phpPath = Path.Combine(Path.GetPathRoot(projectRoot), "xampp", "php", "php.exe");
        string watcherPath = Path.Combine(projectRoot, "scripts", "watch_xml.php");
        string logPath = Path.Combine(projectRoot, "storage", "xml", "watcher.launcher.log");

        if (!File.Exists(phpPath))
            return Fail(logPath, "Không tìm thấy PHP: " + phpPath);
        if (!File.Exists(watcherPath))
            return Fail(logPath, "Không tìm thấy watcher: " + watcherPath);

        if (args.Length > 0 && args[0] == "--check")
        {
            Console.WriteLine("WatcherLauncher: OK");
            return 0;
        }

        bool ownsMutex;
        using (var mutex = new Mutex(true, MutexName, out ownsMutex))
        {
            if (!ownsMutex)
            {
                Console.WriteLine("Watcher đang được mở bởi WatcherLauncher.");
                Console.WriteLine("Nhấn phím bất kỳ để đóng cửa sổ này.");
                Console.ReadKey(true);
                return 0;
            }

            Console.Title = "QL Error - XML Watcher";
            Console.WriteLine("Đang chạy XML watcher...");
            Console.WriteLine("Đóng cửa sổ này để dừng watcher.");
            Console.WriteLine();

            var startInfo = new ProcessStartInfo
            {
                FileName = phpPath,
                Arguments = "\"" + watcherPath + "\"",
                WorkingDirectory = projectRoot,
                UseShellExecute = false,
                CreateNoWindow = false
            };

            try
            {
                using (var watcher = Process.Start(startInfo))
                {
                    if (watcher == null)
                        return Fail(logPath, "Không thể khởi động tiến trình watcher.");
                    watcher.WaitForExit();
                    File.AppendAllText(logPath,
                        DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss")
                        + " Watcher đã dừng, exit code " + watcher.ExitCode + Environment.NewLine);
                    return watcher.ExitCode;
                }
            }
            catch (Exception exception)
            {
                return Fail(logPath, exception.ToString());
            }
        }
    }

    private static int Fail(string logPath, string message)
    {
        try
        {
            Directory.CreateDirectory(Path.GetDirectoryName(logPath));
            File.AppendAllText(logPath,
                DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss") + " " + message + Environment.NewLine);
        }
        catch { }
        Console.Error.WriteLine(message);
        Console.Error.WriteLine("Nhấn phím bất kỳ để đóng.");
        Console.ReadKey(true);
        return 1;
    }
}
