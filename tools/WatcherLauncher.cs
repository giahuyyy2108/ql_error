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
        string logPath = Path.Combine(projectRoot, "storage", "watcher", "logs",
            DateTime.Now.ToString("yyyyMMdd"), "watcher.launcher.log");

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

            try
            {
                var watchers = new Process[2];
                for (int index = 0; index < watchers.Length; index++)
                {
                    var startInfo = new ProcessStartInfo
                    {
                        FileName = phpPath,
                        Arguments = "\"" + watcherPath + "\" --worker-id=" + (index + 1),
                        WorkingDirectory = projectRoot,
                        UseShellExecute = false,
                        CreateNoWindow = false
                    };
                    watchers[index] = Process.Start(startInfo);
                    if (watchers[index] == null)
                        return Fail(logPath, "Không thể khởi động worker " + (index + 1) + ".");
                }
                for (int index = 0; index < watchers.Length; index++)
                {
                    watchers[index].WaitForExit();
                    File.AppendAllText(logPath,
                        DateTime.Now.ToString("yyyy-MM-dd HH:mm:ss")
                        + " Worker " + (index + 1) + " đã dừng, exit code "
                        + watchers[index].ExitCode + Environment.NewLine);
                    watchers[index].Dispose();
                }
                return 0;
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
