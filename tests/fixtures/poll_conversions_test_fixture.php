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
 * Test fixture for the poll stale conversions test class.
 *
 * @package     local_smartmedia
 * @author      Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright   2022 Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

return [
    'getObject' => [
        'ContentLength' => 6,
        'ContentType' => 'text/html',
        'ETag' => '"6805f2cfc46c0f04559748bb039d69ae"',
        'LastModified' => 1,
        'Metadata' => [
        ],
        'TagCount' => 2,
        'VersionId' => 'null',
        'Body' => '123abc'
    ],
    'listObjects' => [
        'Name' => 'smt1566767930-output',
        'MaxKeys' => 1000,
        'Prefix' => 'SampleVideo1mb/conversions/',
        'Marker' => '',
        'EncodingType' => 'url',
        'IsTruncated' => false,
        'Contents' => [
            0 => [
                'LastModified' => '2019-09-11T01:27:21.000Z',
                'ETag' => '"a2f593cbf4c2a49c24d2e9b79da9e3d1"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-100070.mp4',
                'Owner' => [
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ],
                'Size' => 1237366,
            ]
        ]
    ],
    'listObjectsEmpty' => [
        'Name' => 'smt1566767930-output',
        'MaxKeys' => 1000,
        'Prefix' => 'SampleVideo1mb/conversions/',
        'Marker' => '',
        'EncodingType' => 'url',
        'IsTruncated' => false,
        'Contents' => []
    ],
    'deleteObject' => [
        'DeleteMarker' => true,
        'RequestCharged' => 'requester',
        'VersionId' => '123',
    ]
];
