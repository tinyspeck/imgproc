imgproc
=======

Image processing is complicated and annoying.
There are dozens of utilities with millions settings.
The documentation, where it even exists, was surely created by idiots.

This simple tool allows you to play around with various utilities, by picking a source image and command line arguments.
The output files are displayed and analyzed, along with the time taken to process.

The tool is currently set up just for PNGs, but other formats would be easy to add.
It will display PNG color mode, channel-depth, dimensions and chunk lists.


Installation
------------

* Stick it on a web server that has PHP.
* Create a directory full of input images.
* Create an empty directory for output, which the webserver can write to.
* Modify the paths and urls at the top of <code>index.php</code> to match your setup.
* Modify the list of utilities and their paths/commands to match whatever you're testing.
* ...
* Profit!

