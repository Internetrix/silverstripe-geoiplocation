<?php

/*

+-----------------------------------------------------------------+
|   Created by Chirag Mehta - http://chir.ag/projects/geoiploc    |
|-----------------------------------------------------------------|
|                 For PHP GeoIPLocation Library                   |
+-----------------------------------------------------------------+

All the functions, data conversion etc. have been written specifically
for the PHP GeoIPLocation Library by Chirag Mehta.

GeoIPLoc code & data last updated: Wed Jun 25 16:13:19 PDT 2014.
Note: This library is updated automatically once a day.

This library is released under the: Creative Commons License: Attribution 2.5
http://creativecommons.org/licenses/by/2.5/

The IP Country data is from: http://Software77.net (A Webnet77.com Company)

Please review the following copy of license for more information:

# INFORMATION AND NOTES ON IpToCountry.csv.gz
# ===========================================
#
# ------------------------------------------------------------------------------
# LICENSE
# =======
# This database is provided FREE under the terms of the
# GENERAL PUBLIC LICENSE, June 1991
# ------------------------------------------------------------------------------
#
# Generator         : ip.pl on http://Software77.net (A Webnet77.com Company)
# Software Author   : BRM
# Contact           : http://Webnet77.com/contact.html
# Download          : http://software77.net/cgi-bin/ip-country/geo-ip.pl
#
# IMPORTANT NOTES
# ===============
# If you discover a bug in the database, please let us know at the contact
# address above.
#
# What this database is
# =====================
#
# This Database is operated and maintained by Webnet77 and updated every 1
# days and represents [almost] all  2 billion IP numbers [approximately] in use on the
# internet today.
#
# This Database is automatically reconstituted every 1 days by special
# software running on our servers. The bottom of the main page shows how long ago
# it was updated as well as giving an indication of when the next update will
# take place.
# ------------------------------------------------------------------------------
#
# FILE FORMAT
# ===========
#
#      --------------------------------------------------------------
#      All lines beginning with either "#" or whitespace are comments
#      --------------------------------------------------------------
#
# IP FROM      IP TO        REGISTRY  ASSIGNED   CTRY CNTRY COUNTRY
# "1346797568","1346801663","ripencc","20010601","IL","ISR","ISRAEL"
#
# IP FROM   : Numerical representation of IP address.
#             Example: (from Right to Left)
#             1.2.3.4 = 4 + (3 * 256) + (2 * 256 * 256) + (1 * 256 * 256 * 256)
#             is 4 + 768 + 13,1072 + 16,777,216 = 16,909,060
#
# REGISTRY  : apcnic, arin, lacnic, ripencc and afrinic
#             Also included as of April 22, 2005 are the IANA IETF Reserved
#             address numbers. These are important since any source claiming
#             to be from one of these IP's must be spoofed.
#
# ASSIGNED  : The date this IP or block was assigned. (In Epoch seconds)
#             NOTE: Where the allocation or assignment has been transferred from
#                   one registry to another, the date represents the date of first
#                   assignment or allocation as received in from the original RIR.
#                   It is noted that where records do not show a date of first
#                   assignment, the date is given as "0".
#
# CTRY      : 2 character international country code
#             NOTE: ISO 3166 2-letter code of the organisation to which the
#             allocation or assignment was made, and the enumerated variances of:
#           AP - non-specific Asia-Pacific location
#           CS - Serbia and Montenegro (Formally Czechoslovakia)
#           YU - Serbia and Montenegro (Formally Yugoslavia) (Being phased out)
#           EU - non-specific European Union location
#           FX - France, Metropolitan
#           PS - Palestinian Territory, Occupied
#           UK - United Kingdom (standard says GB)
#         * ZZ - IETF RESERVED address space.
#
#             These values are not defined in ISO 3166 but are widely used.
#           * IANA Reserved Address space
#
# CNTRY     : Country Abbreviation. Usually 3 Character representation
#
# COUNTRY   : Country Name. Full Country Name.
#
# Countries falling under AFRINIC now show correctly (June 27, 2005)
# ------------------------------------------------------------------------------
# THIS DATABSE IS PROVIDED WITHOUT ANY WARRANTY WHATSOEVER. USE ENTIRELY AT YOUR
# OWN RISK. NO LIABILITY WHATSOEVER, OF ANY NATURE, WILL BE ASSUMEND BY
# Webnet77.com, IT'S DISTRIBUTORS, RESELLERS OR AGENTS. SHOULD THE DATABASE
# PROVE TO BE FAULTY, CAUSE YOU LOSS OR OTHER FINANCIAL DAMAGE, YOU AGREE YOU
# HAVE NO CLAIM AGINST Webnet77.com IT'S DISTRIBUTORS, RESELLERS OR AGENTS. IF
# YOU DO NOT ACCEPT THESE TERMS YOU MAY NOT USE THIS DATABASE.
# ------------------------------------------------------------------------------
#
#                            © 2002-12:08:03 Webnet77.com
#
#
#
#

*/


/* usage:

     $cCode = getCountryFromIP($ip);           // returns country code by default
     $cCode = getCountryFromIP($ip, "code");   // you can specify code - optional
     $cAbbr = getCountryFromIP($ip, "AbBr");   // returns country abbreviation - case insensitive
     $cName = getCountryFromIP($ip, " NamE "); // full name of country - spaces are trimmed

     $ip must be of the form "192.168.1.100"
     $type can be "code", "abbr", "name", or omitted

  ip cacheing:

     this function has a simple cache that works pretty well when you are calling
     getCountryFromIP thousands of times in the same script and IPs are repeated e.g.
     while parsing access logs. Without caching, each IP would be searched everytime
     you called this function. The only time caching would slow down performance
     is if you have 100k+ unique IP addresses. But then you should use a dedicated
     box for GeoLocation anyway and of course feel free to optimize this script.
*/

function getCountryFromIP($ip, $type = "code")
{
	//gloabl data are in sample-GLOBALS.data file
  global $geoipaddrfrom, $geoipaddrupto;
  global $geoipctry, $geoipcntry, $geoipcountry;
  global $geoipcount, $geoipcache;

  if(strpos($ip, ".") === false)
    return "";

  $ip = substr("0000000000" . sprintf("%u", ip2long($ip)), -10);
  $ipn = base64_encode($ip);

  if(isset($geoipcache[$ipn])) // search in cache
  {
    $ct = $geoipcache[$ipn];
  }
  else // search in IP Address array
  {
    $from = 0;
    $upto = $geoipcount;
    $ct   = "ZZ"; // default: Reserved or Not Found

    // simple binary search within the array for given text-string within IP range
    while($upto > $from)
    {
      $idx = $from + intval(($upto - $from)/2);
      $loip = substr("0000000000" . $geoipaddrfrom[$idx], -10);
      $hiip = substr("0000000000" . $geoipaddrupto[$idx], -10);

      if($loip <= $ip && $hiip >= $ip)
      {
        $ct = $geoipctry[$idx];
        break;
      }
      else if($loip > $ip)
      {
        if($upto == $idx)
          break;
        $upto = $idx;
      }
      else if($hiip < $ip)
      {
        if($from == $idx)
          break;
        $from = $idx;
      }
    }

    // cache the country code
    $geoipcache[$ipn] = $ct;
  }

  $type = trim(strtolower($type));

  if($type == "abbr")
    $ct = $geoipcntry[$ct];
  else if($type == "name")
    $ct = $geoipcountry[$ct];

  return $ct;
}

?>