using System;
using System.Diagnostics;
using System.IO;
using System.Linq;

namespace Elevator
{
	class Program
	{
		static void Main(string[] args)
		{
			if (args.Length < 2)
			{
				Console.WriteLine("Usage: elevator.exe path program [arg1] [arg2] [arg3] ...");
				Console.ReadKey();
				return;
			}

			var arguments = string.Join(" ", args.Skip(2).Select(t => string.Format("\"{0}\"", t)));
			var fileName = args[1];

			Console.WriteLine("Running: {0} {1}", fileName, arguments);
			Console.WriteLine("----------------------------");

			try
			{
				var process = new Process
				{
					StartInfo =
					{
						FileName = fileName,
						Arguments = arguments,
						UseShellExecute = false,
						RedirectStandardInput = false,
						WorkingDirectory = Path.GetFullPath(args[0])
					}
				};

				process.Start();
				process.WaitForExit();
			}
			catch (Exception ex)
			{
				Console.ForegroundColor = ConsoleColor.Red;
				Console.WriteLine(ex.Message);
				Console.ForegroundColor = ConsoleColor.Gray;
			}

			Console.WriteLine("----------------------------");
			Console.WriteLine("Press any key");
			Console.ReadKey();
		}
	}
}
