# File Distribution Library

package Stsbl::IServ::FileDistribution;
use warnings;
use utf8;
use strict;
use IServ::SSH;
use Stsbl::IServ::SCP;
use Stsbl::IServ::OpenSSH;

BEGIN
{
  use Exporter;
  our @ISA = qw(Exporter);
  our @EXPORT = qw(msg linux_start linux_stop);
}

sub msg(@)
{
  my ($ip) = $_;
  warn "$ip: $_" for @_;
}

sub linux_start
{
  my $ip = $_;
  Stsbl::IServ::SCP::scp($ip, "/var/lib/stsbl/file-distribution/scripts/setup.sh", ":/tmp/fd-setup.sh");
  Stsbl::IServ::SCP::scp($ip, "/usr/share/iserv/samba/file-distribution/login.sh", ":/etc/X11/Xsession.d/61iserv_file-distribution");
  my %ssh = Stsbl::IServ::OpenSSH::openssh_run $ip, "bash /tmp/fd-setup.sh start";
  my (@stdout, @stderr);
  @stdout = split /\n/, $ssh{stdout};
  @stderr = split /\n/, $ssh{stderr};
  my $cnt = 0;
  foreach my $line (@stdout)
  {
    undef $stdout[$cnt] if $line eq "";
    $cnt++;
  }
  msg @stdout if scalar @stdout > 1;
  msg @stderr if scalar @stderr > 1;
}

sub linux_stop
{
  my $ip = $_;
  Stsbl::IServ::SCP::scp($ip, "/var/lib/stsbl/file-distribution/scripts/setup.sh", ":/tmp/fd-setup.sh");
  IServ::SSH::ssh_run $ip, "rm -f /etc/X11/Xsession.d/61iserv_file-distribution";
  my %ssh = Stsbl::IServ::OpenSSH::openssh_run $ip, "bash /tmp/fd-setup.sh stop";
  my (@stdout, @stderr);
  @stdout = split /\n/, $ssh{stdout};
  @stderr = split /\n/, $ssh{stderr};
  my $cnt = 0;
  foreach my $line (@stdout)
  {
    undef $stdout[$cnt] if $line eq "";
    $cnt++;
  }
  msg @stdout if scalar @stdout > 1;
  msg @stderr if scalar @stderr > 1;
}

1;
