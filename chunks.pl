#!/usr/bin/perl

use warnings;
use strict;
use Data::Dumper;

open F, $ARGV[0] or die $!;

# header first
my $buffer;
read F, $buffer, 8;

my $prev_chunk = '';
my $prev_count = 0;
my $prev_size = 0;

my @lines;

# now read chunks
while (!eof F){

	read F, $buffer, 4;
	my ($len) = unpack('N', $buffer);

	read F, $buffer, 4;

	if ($buffer eq $prev_chunk){

		$prev_count++;
		$prev_size += $len;

	}else{

		if ($prev_count){
			push @lines, [$prev_chunk.($prev_count > 1 ? " x$prev_count" : ""), "$prev_size bytes"];
		}

		$prev_chunk = $buffer;
		$prev_count = 1;
		$prev_size = $len;

	}

	read F, $buffer, $len;
	read F, $buffer, 4;
}

if ($prev_count){
	push @lines, [$prev_chunk.($prev_count > 1 ? " x$prev_count" : ""), "$prev_size bytes"];
}

my $max_a = 0;
my $max_b = 0;

for my $pair (@lines){
	$max_a = length($pair->[0]) if length($pair->[0]) > $max_a;
	$max_b = length($pair->[1]) if length($pair->[1]) > $max_b;
}

for my $pair (@lines){

	print $pair->[0].(" " x ($max_a - length($pair->[0])));
	print " : ";
	print((" " x ($max_b - length($pair->[1]))).$pair->[1]."\n");
}
