This folder contains tools for use with PFM.

# Elevator
Elevator is a simple .net application that elevates whatever commands are passed into it to an admin context.

This is required for the bat files that interact with the Vagrant VMWare provider on Windows. The provider requires that all commands on it are executed in an admin context. As a result, you would have to right click any bat that executes these commands and select "run as administrator".

Elevator fixes this by doing this automatically, so you simply have to double click on the bat file and accept the UAC dialog.

You use it simply be invoking
> elevator.exe path command arg1 arg2 arg3 ...

Where "path" will be the working directory where the program is executed, "command" is the command you wish to execute, and "arg..." are the arguments you wish to supply to "command".

Note that "path" is relative to the current working directory and can be a relative path.