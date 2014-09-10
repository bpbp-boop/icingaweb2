# Class: icinga2
#
#   This class installs Icinga 2.
#
# Requires:
#
#   icinga_packages
#   icinga2::feature
#
# Sample Usage:
#
#   include icinga2
#
class icinga2 {
  include icinga_packages

  service { 'icinga2':
    ensure  => running,
    enable  => true,
    require => Package['icinga2']
  }

  package { [
    'icinga2', 'icinga2-doc', 'icinga2-debuginfo' ]:
    ensure  => latest,
    require => Class['icinga_packages'],
  }

  icinga2::feature { [ 'statusdata', 'command', 'compatlog' ]: }
}