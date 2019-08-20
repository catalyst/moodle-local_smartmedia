<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Test fixture for the queue process test class.
 *
 * @package     local_smartmedia
 * @copyright   2019 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

return array(
    'listobjects' => array (
            'IsTruncated' => false,
            'Marker' => '',
            'Contents' =>
            array (
                0 =>
                array (
                    'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb.mp3',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-08-19 05:39:45.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"23896cb05e9e87f489b1e28c9654661c"',
                    'Size' => 129296,
                    'StorageClass' => 'STANDARD',
                ),
                1 =>
                array (
                    'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb.mp4',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-08-19 05:39:42.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"a2f593cbf4c2a49c24d2e9b79da9e3d1"',
                    'Size' => 1237366,
                    'StorageClass' => 'STANDARD',
                ),
                2 =>
                array (
                    'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb.webm',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-08-19 05:39:54.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"d00db10271cd27abfaabc27550ff4bc0"',
                    'Size' => 1489528,
                    'StorageClass' => 'STANDARD',
                ),
            ),
            'Name' => 'smt1566116966-output',
            'Prefix' => 'SampleVideo1mb/conversions/',
            'MaxKeys' => 1000,
            'EncodingType' => 'url',
            '@metadata' =>
            array (
                'statusCode' => 200,
                'effectiveUri' => 'https://smt1566116966-output.s3.ap-southeast-2.amazonaws.com/?max-keys=1000&prefix=SampleVideo1mb%2Fconversions%2F&encoding-type=url',
                'headers' =>
                array (
                    'x-amz-id-2' => 'FW3somoV2jF1gEFgCc6KS5Xz8y89gIKeQ81mekhAMpq+7AlV8z+AZYuzE3ALzDJjBt5A0rqQuow=',
                    'x-amz-request-id' => 'C437CE3D2D6EC88D',
                    'date' => 'Tue, 20 Aug 2019 10:27:42 GMT',
                    'x-amz-bucket-region' => 'ap-southeast-2',
                    'content-type' => 'application/xml',
                    'transfer-encoding' => 'chunked',
                    'server' => 'AmazonS3',
                ),
                'transferStats' =>
                array (
                    'http' =>
                    array (
                        0 =>
                        array (
                        ),
                    ),
                ),
            ),
        ),
);
