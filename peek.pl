#!/usr/bin/perl

use warnings;
use strict;

open F, $ARGV[0] or die $!;
my $junk;
my $buffer;
read F, $junk, 16;
read F, $buffer, 10;

my ($w, $h, $depth, $type) = unpack('NNCC', $buffer);

my $type_map = {
	0 => "Grayscale",
	1 => "Indexed grayscale (INVALID)",
	2 => "Truecolor",
	3 => "Indexed",
	4 => "Grayscale & alpha",
	5 => "Indexed grayscale & alpha (INVALID)",
	6 => "Truecolor & alpha",
	7 => "Indexed & alpha (INVALID)",
};

my $type_channels = {
	0 => 1,
	2 => 3,
	3 => 1,
	4 => 2,
	6 => 4,
};

my $cols_map = {
	1 => 2,
	2 => 4,
	4 => 16,
	8 => 256,
};

my $channels = 0 + $type_channels->{$type};
my $colors = $cols_map->{$depth} ** $channels ;

print "Size     : $w x $h\n";
print "Type     : $type ($type_map->{$type})\n";
print "Depth    : $depth\n";
print "Channels : $channels\n";
print "Colors   : $colors\n";
