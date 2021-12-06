# Court booking web-interface

Hi, this is the sourcecode for the court booking website I wrote for [Trinity
College, Cambridge](http://www.trin.cam.ac.uk/fieldclub) while a student there.

The code is the first largish project that I have written from scratch in PHP,
so some parts are not is clean as they could be. Also I didn't know of ORM-type
frameworks in PHP at this stage, so I sort of wrote my own.

# Features

- Admin interface:
	- in which courts, sports and teams are added. For each court a
	  number of sports may be assigned. And each team plays a specific sport. 
	- user administration, e.g. disabling access, changing password or setting
	team captains (this gives extra booking rights).
- Block booking feature for team captains making recurring bookings simpler.
- Automatic booking reminders.
- Integration of Google maps to show courts.
- Multi-level access granularity. 

# Installation

See installation instructions in `install/INSTALL.md`.

# License

This code is released under the CC-NC-SA license (see the LICENSE file for full
details).
