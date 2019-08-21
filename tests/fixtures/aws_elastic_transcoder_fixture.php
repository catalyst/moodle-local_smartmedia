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
 * Test fixture for \Aws\ElasticTranscoder\ElasticTranscoderClient method calls.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

return [
    // Fixtures for \Aws\ElasticTranscoder\ElasticTranscoderClient->readPreset.
    'readPreset' => [
        'High Definition' => [
            'Preset' => [
                'Id' => '1351620000001-000001',
                'Arn' => 'arn => aws => elastictranscoder => ap-southeast-2 => 512561797349 => preset\/1351620000001-000001',
                'Name' => 'System preset =>  Generic 1080p',
                'Description' => 'System preset generic 1080p',
                'Container' => 'mp4',
                'Audio' => [
                'Codec' => 'AAC',
                    'SampleRate' => '44100',
                    'BitRate' => '160',
                    'Channels' => '2',
                    'CodecOptions' => [
                    'Profile' => 'AAC-LC'
                    ]
                ],
                'Video' => [
                'Codec' => 'H.264',
                    'CodecOptions' => [
                    'ColorSpaceConversionMode' => 'None',
                        'InterlacedMode' => 'Progressive',
                        'Level' => '4',
                        'MaxReferenceFrames' => '3',
                        'Profile' => 'baseline'
                    ],
                    'KeyframesMaxDist' => '90',
                    'FixedGOP' => 'false',
                    'BitRate' => '5400',
                    'FrameRate' => '29.97',
                    'AspectRatio' => '16 => 9',
                    'MaxWidth' => '1920',
                    'MaxHeight' => '1080',
                    'DisplayAspectRatio' => 'auto',
                    'SizingPolicy' => 'ShrinkToFit',
                    'PaddingPolicy' => 'NoPad',
                    'Watermarks' => [
                        [
                            'Id' => 'TopLeft',
                            'MaxWidth' => '10%',
                            'MaxHeight' => '10%',
                            'SizingPolicy' => 'ShrinkToFit',
                            'HorizontalAlign' => 'Left',
                            'HorizontalOffset' => '10%',
                            'VerticalAlign' => 'Top',
                            'VerticalOffset' => '10%',
                            'Opacity' => '100',
                            'Target' => 'Content'
                        ],
                        [
                            'Id' => 'TopRight',
                            'MaxWidth' => '10%',
                            'MaxHeight' => '10%',
                            'SizingPolicy' => 'ShrinkToFit',
                            'HorizontalAlign' => 'Right',
                            'HorizontalOffset' => '10%',
                            'VerticalAlign' => 'Top',
                            'VerticalOffset' => '10%',
                            'Opacity' => '100',
                            'Target' => 'Content'
                        ],
                        [
                            'Id' => 'BottomLeft',
                            'MaxWidth' => '10%',
                            'MaxHeight' => '10%',
                            'SizingPolicy' => 'ShrinkToFit',
                            'HorizontalAlign' => 'Left',
                            'HorizontalOffset' => '10%',
                            'VerticalAlign' => 'Bottom',
                            'VerticalOffset' => '10%',
                            'Opacity' => '100',
                            'Target' => 'Content'
                        ],
                        [
                            'Id' => 'BottomRight',
                            'MaxWidth' => '10%',
                            'MaxHeight' => '10%',
                            'SizingPolicy' => 'ShrinkToFit',
                            'HorizontalAlign' => 'Right',
                            'HorizontalOffset' => '10%',
                            'VerticalAlign' => 'Bottom',
                            'VerticalOffset' => '10%',
                            'Opacity' => '100',
                            'Target' => 'Content'
                        ]
                    ]
                ],
                'Thumbnails' => [
                'Format' => 'png',
                    'Interval' => '60',
                    'MaxWidth' => '192',
                    'MaxHeight' => '144',
                    'SizingPolicy' => 'ShrinkToFit',
                    'PaddingPolicy' => 'NoPad'
                ],
                'Type' => 'System'
            ]
        ],
        'Standard Definition' => [
            'Preset' => [
                'Id' => '1351620000001-000020',
                'Arn' => 'arn => aws => elastictranscoder => ap-southeast-2 => 512561797349 => preset\/1351620000001-000020',
                'Name' => 'System preset =>  Generic 480p 16 => 9',
                'Description' => 'System preset generic 480p 16 => 9',
                'Container' => 'mp4',
                'Audio' => [
                'Codec' => 'AAC',
                    'SampleRate' => '44100',
                    'BitRate' => '128',
                    'Channels' => '2',
                    'CodecOptions' => [
                    'Profile' => 'AAC-LC'
                    ]
                ],
                'Video' => [
                'Codec' => 'H.264',
                    'CodecOptions' => [
                    'ColorSpaceConversionMode' => 'None',
                        'InterlacedMode' => 'Progressive',
                        'Level' => '3.1',
                        'MaxReferenceFrames' => '3',
                        'Profile' => 'baseline'
                    ],
                    'KeyframesMaxDist' => '90',
                    'FixedGOP' => 'false',
                    'BitRate' => '1200',
                    'FrameRate' => '29.97',
                    'MaxWidth' => '854',
                    'MaxHeight' => '480',
                    'DisplayAspectRatio' => 'auto',
                    'SizingPolicy' => 'ShrinkToFit',
                    'PaddingPolicy' => 'NoPad',
                    'Watermarks' => [
                        [
                            'Id' => 'TopLeft',
                            'MaxWidth' => '10%',
                            'MaxHeight' => '10%',
                            'SizingPolicy' => 'ShrinkToFit',
                            'HorizontalAlign' => 'Left',
                            'HorizontalOffset' => '10%',
                            'VerticalAlign' => 'Top',
                            'VerticalOffset' => '10%',
                            'Opacity' => '100',
                            'Target' => 'Content'
                        ],
                        [
                            'Id' => 'TopRight',
                            'MaxWidth' => '10%',
                            'MaxHeight' => '10%',
                            'SizingPolicy' => 'ShrinkToFit',
                            'HorizontalAlign' => 'Right',
                            'HorizontalOffset' => '10%',
                            'VerticalAlign' => 'Top',
                            'VerticalOffset' => '10%',
                            'Opacity' => '100',
                            'Target' => 'Content'
                        ],
                        [
                            'Id' => 'BottomLeft',
                            'MaxWidth' => '10%',
                            'MaxHeight' => '10%',
                            'SizingPolicy' => 'ShrinkToFit',
                            'HorizontalAlign' => 'Left',
                            'HorizontalOffset' => '10%',
                            'VerticalAlign' => 'Bottom',
                            'VerticalOffset' => '10%',
                            'Opacity' => '100',
                            'Target' => 'Content'
                        ],
                        [
                            'Id' => 'BottomRight',
                            'MaxWidth' => '10%',
                            'MaxHeight' => '10%',
                            'SizingPolicy' => 'ShrinkToFit',
                            'HorizontalAlign' => 'Right',
                            'HorizontalOffset' => '10%',
                            'VerticalAlign' => 'Bottom',
                            'VerticalOffset' => '10%',
                            'Opacity' => '100',
                            'Target' => 'Content'
                        ]
                    ]
                ],
                'Thumbnails' => [
                'Format' => 'png',
                    'Interval' => '60',
                    'MaxWidth' => '192',
                    'MaxHeight' => '108',
                    'SizingPolicy' => 'ShrinkToFit',
                    'PaddingPolicy' => 'NoPad'
                ],
                'Type' => 'System'
            ]
        ],
        'Audio' => [
            'Preset' => [
                'Id' => '1351620000001-300010',
                'Arn' => 'arn => aws => elastictranscoder => ap-southeast-2 => 512561797349 => preset\/1351620000001-300010',
                'Name' => 'System preset =>  Audio MP3 - 320k',
                'Description' => 'System preset =>  Audio MP3 - 320 kilobits\/second',
                'Container' => 'mp3',
                'Audio' => [
                    'Codec' => 'mp3',
                    'SampleRate' => '44100',
                    'BitRate' => '320',
                    'Channels' => '2'
                ],
                'Type' => 'System'
            ]
        ]
    ]
];
