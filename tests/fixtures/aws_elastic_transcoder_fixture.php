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
        ['Preset'=> array (
            'Id' => '1351620000001-200015',
            'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-200015',
            'Name' => 'System preset: HLS Video - 2M',
            'Description' => 'System preset: HLS Video - 2M',
            'Container' => 'ts',
            'Video' =>
            array (
                'Codec' => 'H.264',
                'CodecOptions' =>
                array (
                    'BufferSize' => '16848',
                    'ColorSpaceConversionMode' => 'None',
                    'InterlacedMode' => 'Progressive',
                    'Level' => '3.1',
                    'MaxBitRate' => '1872',
                    'MaxReferenceFrames' => '3',
                    'Profile' => 'main',
                ),
                'KeyframesMaxDist' => '90',
                'FixedGOP' => 'true',
                'BitRate' => '1872',
                'FrameRate' => 'auto',
                'MaxFrameRate' => '60',
                'MaxWidth' => '1024',
                'MaxHeight' => '768',
                'DisplayAspectRatio' => 'auto',
                'SizingPolicy' => 'ShrinkToFit',
                'PaddingPolicy' => 'NoPad',
                'Watermarks' =>
                array (
                    0 =>
                    array (
                        'Id' => 'TopLeft',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Left',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Top',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    1 =>
                    array (
                        'Id' => 'TopRight',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Right',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Top',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    2 =>
                    array (
                        'Id' => 'BottomLeft',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Left',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Bottom',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    3 =>
                    array (
                        'Id' => 'BottomRight',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Right',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Bottom',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                ),
            ),
            'Thumbnails' =>
            array (
                'Format' => 'png',
                'Interval' => '300',
                'MaxWidth' => '192',
                'MaxHeight' => '108',
                'SizingPolicy' => 'ShrinkToFit',
                'PaddingPolicy' => 'NoPad',
            ),
            'Type' => 'System',
        )],
        ['Preset'=> array (
            'Id' => '1351620000001-500030',
            'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-500030',
            'Name' => 'System preset: MPEG-Dash Video - 2.4M',
            'Description' => 'System preset: MPEG-Dash Video - 2.4M',
            'Container' => 'fmp4',
            'Video' =>
            array (
                'Codec' => 'H.264',
                'CodecOptions' =>
                array (
                    'BufferSize' => '4800',
                    'ColorSpaceConversionMode' => 'None',
                    'InterlacedMode' => 'Progressive',
                    'Level' => '3.1',
                    'MaxBitRate' => '2400',
                    'MaxReferenceFrames' => '3',
                    'Profile' => 'main',
                ),
                'KeyframesMaxDist' => '60',
                'FixedGOP' => 'true',
                'BitRate' => '2400',
                'FrameRate' => '30',
                'MaxWidth' => '854',
                'MaxHeight' => '480',
                'DisplayAspectRatio' => 'auto',
                'SizingPolicy' => 'ShrinkToFit',
                'PaddingPolicy' => 'NoPad',
                'Watermarks' =>
                array (
                    0 =>
                    array (
                        'Id' => 'TopLeft',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Left',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Top',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    1 =>
                    array (
                        'Id' => 'TopRight',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Right',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Top',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    2 =>
                    array (
                        'Id' => 'BottomLeft',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Left',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Bottom',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    3 =>
                    array (
                        'Id' => 'BottomRight',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Right',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Bottom',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                ),
            ),
            'Thumbnails' =>
            array (
                'Format' => 'png',
                'Interval' => '300',
                'MaxWidth' => '192',
                'MaxHeight' => '108',
                'SizingPolicy' => 'ShrinkToFit',
                'PaddingPolicy' => 'NoPad',
            ),
            'Type' => 'System',
        )],
        ['Preset'=> array (
            'Id' => '1351620000001-200045',
            'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-200045',
            'Name' => 'System preset: HLS Video - 600k',
            'Description' => 'System preset: HLS Video - 600k',
            'Container' => 'ts',
            'Video' =>
            array (
                'Codec' => 'H.264',
                'CodecOptions' =>
                array (
                    'BufferSize' => '4248',
                    'ColorSpaceConversionMode' => 'None',
                    'InterlacedMode' => 'Progressive',
                    'Level' => '3',
                    'MaxBitRate' => '472',
                    'MaxReferenceFrames' => '3',
                    'Profile' => 'baseline',
                ),
                'KeyframesMaxDist' => '90',
                'FixedGOP' => 'true',
                'BitRate' => '472',
                'FrameRate' => 'auto',
                'MaxFrameRate' => '60',
                'MaxWidth' => '480',
                'MaxHeight' => '320',
                'DisplayAspectRatio' => 'auto',
                'SizingPolicy' => 'ShrinkToFit',
                'PaddingPolicy' => 'NoPad',
                'Watermarks' =>
                array (
                    0 =>
                    array (
                        'Id' => 'TopLeft',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Left',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Top',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    1 =>
                    array (
                        'Id' => 'TopRight',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Right',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Top',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    2 =>
                    array (
                        'Id' => 'BottomLeft',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Left',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Bottom',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    3 =>
                    array (
                        'Id' => 'BottomRight',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Right',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Bottom',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                ),
            ),
            'Thumbnails' =>
            array (
                'Format' => 'png',
                'Interval' => '300',
                'MaxWidth' => '192',
                'MaxHeight' => '108',
                'SizingPolicy' => 'ShrinkToFit',
                'PaddingPolicy' => 'NoPad',
            ),
            'Type' => 'System',
        )],
        ['Preset'=> array (
            'Id' => '1351620000001-500050',
            'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-500050',
            'Name' => 'System preset: MPEG-Dash Video - 600k',
            'Description' => 'System preset: MPEG-Dash Video - 600k',
            'Container' => 'fmp4',
            'Video' =>
            array (
                'Codec' => 'H.264',
                'CodecOptions' =>
                array (
                    'BufferSize' => '1200',
                    'ColorSpaceConversionMode' => 'None',
                    'InterlacedMode' => 'Progressive',
                    'Level' => '3',
                    'MaxBitRate' => '600',
                    'MaxReferenceFrames' => '1',
                    'Profile' => 'main',
                ),
                'KeyframesMaxDist' => '60',
                'FixedGOP' => 'true',
                'BitRate' => '600',
                'FrameRate' => '30',
                'MaxWidth' => '426',
                'MaxHeight' => '240',
                'DisplayAspectRatio' => 'auto',
                'SizingPolicy' => 'ShrinkToFit',
                'PaddingPolicy' => 'NoPad',
                'Watermarks' =>
                array (
                    0 =>
                    array (
                        'Id' => 'TopLeft',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Left',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Top',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    1 =>
                    array (
                        'Id' => 'TopRight',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Right',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Top',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    2 =>
                    array (
                        'Id' => 'BottomLeft',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Left',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Bottom',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                    3 =>
                    array (
                        'Id' => 'BottomRight',
                        'MaxWidth' => '10%',
                        'MaxHeight' => '10%',
                        'SizingPolicy' => 'ShrinkToFit',
                        'HorizontalAlign' => 'Right',
                        'HorizontalOffset' => '10%',
                        'VerticalAlign' => 'Bottom',
                        'VerticalOffset' => '10%',
                        'Opacity' => '100',
                        'Target' => 'Content',
                    ),
                ),
            ),
            'Thumbnails' =>
            array (
                'Format' => 'png',
                'Interval' => '300',
                'MaxWidth' => '192',
                'MaxHeight' => '108',
                'SizingPolicy' => 'ShrinkToFit',
                'PaddingPolicy' => 'NoPad',
            ),
            'Type' => 'System',
        )
    ]
    ]
];
