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
    'listobjects' => array(
        'Name' => 'smt1566767930-output',
        'MaxKeys' => 1000,
        'Prefix' => 'SampleVideo1mb/conversions/',
        'Marker' => '',
        'EncodingType' => 'url',
        'IsTruncated' => false,
        'Contents' => array(
            0 => array(
                'LastModified' => '2019-09-11T01:27:21.000Z',
                'ETag' => '"a2f593cbf4c2a49c24d2e9b79da9e3d1"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-100070.mp4',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 1237366,
            ),
            1 => array(
                'LastModified' => '2019-09-11T01:38:33.000Z',
                'ETag' => '"50fd2c8836546bcfb0381a72cb13f850"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-200035.ts',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 723988,
            ),
            2 => array(
                'LastModified' => '2019-09-11T01:38:33.000Z',
                'ETag' => '"eff3d7595910c23cca64e0fbe3e23f60"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-200035_iframe.m3u8',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 323,
                'Body' => <<<'EOF'
#EXTM3U
#EXT-X-VERSION:4
#EXT-X-MEDIA-SEQUENCE:0
#EXT-X-ALLOW-CACHE:YES
#EXT-X-TARGETDURATION:4
#EXT-X-I-FRAMES-ONLY
#EXTINF:3.0000000000000004,
#EXT-X-BYTERANGE:77644@564
SampleVideo1mb_1351620000001-200035.ts
#EXTINF:2.2799999999999994,
#EXT-X-BYTERANGE:88736@381076
SampleVideo1mb_1351620000001-200035.ts
#EXT-X-ENDLIST
EOF
            ),
            3 => array(
                'LastModified' => '2019-09-11T01:38:33.000Z',
                'ETag' => '"0f77431ecd650efe839490c3c6103e30"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-200035_v4.m3u8',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 302,
                'Body' => <<<'EOF'
#EXTM3U
#EXT-X-VERSION:4
#EXT-X-MEDIA-SEQUENCE:0
#EXT-X-ALLOW-CACHE:YES
#EXT-X-TARGETDURATION:4
#EXTINF:3.0000000000000004,
#EXT-X-BYTERANGE:381076@0
SampleVideo1mb_1351620000001-200035.ts
#EXTINF:2.2799999999999994,
#EXT-X-BYTERANGE:342912@381076
SampleVideo1mb_1351620000001-200035.ts
#EXT-X-ENDLIST
EOF
            ),
            4 => array(
                'LastModified' => '2019-09-11T01:38:33.000Z',
                'ETag' => '"a67c6ce6f65cedf7ef1a1fe99f164ad6"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-200045.ts',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 397808,
            ),
            5 => array(
                'LastModified' => '2019-09-11T01:38:33.000Z',
                'ETag' => '"b67216cfabe5328b6750ae03ae6a9e78"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-200045_iframe.m3u8',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 323,
                'Body' => <<<'EOF'
#EXTM3U
#EXT-X-VERSION:4
#EXT-X-MEDIA-SEQUENCE:0
#EXT-X-ALLOW-CACHE:YES
#EXT-X-TARGETDURATION:4
#EXT-X-I-FRAMES-ONLY
#EXTINF:3.0000000000000004,
#EXT-X-BYTERANGE:44744@564
SampleVideo1mb_1351620000001-200045.ts
#EXTINF:2.2799999999999994,
#EXT-X-BYTERANGE:42300@243648
SampleVideo1mb_1351620000001-200045.ts
#EXT-X-ENDLIST
EOF
            ),
            6 => array(
                'LastModified' => '2019-09-11T01:38:33.000Z',
                'ETag' => '"64d78628c9bb3f52174540cab9c3f077"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-200045_v4.m3u8',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 302,
                'Body' => <<<'EOF'
#EXTM3U
#EXT-X-VERSION:4
#EXT-X-MEDIA-SEQUENCE:0
#EXT-X-ALLOW-CACHE:YES
#EXT-X-TARGETDURATION:4
#EXTINF:3.0000000000000004,
#EXT-X-BYTERANGE:243648@0
SampleVideo1mb_1351620000001-200045.ts
#EXTINF:2.2799999999999994,
#EXT-X-BYTERANGE:154160@243648
SampleVideo1mb_1351620000001-200045.ts
#EXT-X-ENDLIST
EOF
            ),
            7 => array(
                'LastModified' => '2019-09-11T01:27:14.000Z',
                'ETag' => '"23896cb05e9e87f489b1e28c9654661c"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-300020.mp3',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 129296,
            ),
            8 => array(
                'LastModified' => '2019-09-11T01:38:33.000Z',
                'ETag' => '"43baf84a350c3b08a6fa739fa7f0b8eb"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-500040.fmp4',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 799232,
            ),
            9 => array(
                'LastModified' => '2019-09-11T01:38:33.000Z',
                'ETag' => '"fba3e2d1877c8e75024d685ba6cfd320"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_1351620000001-500050.fmp4',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 404039,
            ),
            10 => array(
                'LastModified' => '2019-09-11T01:27:28.000Z',
                'ETag' => '"ee4940aa13dffaa1642f72328c624e93"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_hls_playlist.m3u8',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 538,
                'Body' => <<<'EOF'
#EXTM3U
#EXT-X-I-FRAME-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=8948000,CODECS="avc1.42001e",URI="SampleVideo1mb_1351620000001-200045_iframe.m3u8"
#EXT-X-I-FRAME-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=17784000,CODECS="avc1.4d001e",URI="SampleVideo1mb_1351620000001-200035_iframe.m3u8"
#:PROGRAM-ID=1,BANDWIDTH=646000,RESOLUTION=480x270,CODECS="avc1.42001e"
SampleVideo1mb_1351620000001-200045_v4.m3u8
#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=1202000,RESOLUTION=640x360,CODECS="avc1.4d001e"
SampleVideo1mb_1351620000001-200035_v4.m3u8
EOF
            ),
            11 => array(
                'LastModified' => '2019-09-11T01:27:28.000Z',
                'ETag' => '"8ea8bd457baea8e70b59b0b2d4ffec3e"',
                'StorageClass' => 'STANDARD',
                'Key' => 'SampleVideo1mb/conversions/SampleVideo1mb_mpegdash_playlist.mpd',
                'Owner' => array(
                    'DisplayName' => 'inf+stageaws',
                    'ID' => '355d884e01c713d418ec74ebbe1fd8db4694d97725b0f7016dfec6a0c9c886a1',
                ),
                'Size' => 1259,
                'Body' => <<<'EOF'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<MPD mediaPresentationDuration="PT5.267S" minBufferTime="PT1.500S" profiles="urn:mpeg:dash:profile:isoff-on-demand:2011" type="static" xmlns="urn:mpeg:dash:schema:mpd:2011">
    <Period duration="PT5.267S">
        <AdaptationSet segmentAlignment="true" subsegmentAlignment="true" subsegmentStartsWithSAP="1">
            <Representation bandwidth="614658" codecs="avc1.4d401e" frameRate="30" height="240" id="VIDEO-1" mimeType="video/mp4" startWithSAP="1" width="426">
                <BaseURL>SampleVideo1mb_1351620000001-500050.fmp4</BaseURL>
                <SegmentBase indexRange="915-982" indexRangeExact="true">
                    <Initialization range="0-914"/>
                </SegmentBase>
            </Representation>
            <Representation bandwidth="1213532" codecs="avc1.4d401e" frameRate="30" height="360" id="VIDEO-2" mimeType="video/mp4" startWithSAP="1" width="640">
                <BaseURL>SampleVideo1mb_1351620000001-500040.fmp4</BaseURL>
                <SegmentBase indexRange="914-981" indexRangeExact="true">
                    <Initialization range="0-913"/>
                </SegmentBase>
            </Representation>
        </AdaptationSet>
    </Period>
</MPD>
EOF
            ),
        ),
        '@metadata' => array(
            'statusCode' => 200,
            'effectiveUri' => 'https://smt1566116966-output.s3.ap-southeast-2.amazonaws.com/?max-keys=1000&prefix=SampleVideo1mb%2Fconversions%2F&encoding-type=url',
            'headers' => array(
                'x-amz-id-2' => 'FW3somoV2jF1gEFgCc6KS5Xz8y89gIKeQ81mekhAMpq+7AlV8z+AZYuzE3ALzDJjBt5A0rqQuow=',
                'x-amz-request-id' => 'C437CE3D2D6EC88D',
                'date' => 'Tue, 20 Aug 2019 10:27:42 GMT',
                'x-amz-bucket-region' => 'ap-southeast-2',
                'content-type' => 'application/xml',
                'transfer-encoding' => 'chunked',
                'server' => 'AmazonS3',
            ),
            'transferStats' => array(
                'http' => array(
                    0 => array(
                    ),
                ),
            ),
        ),
    ),
    // Fixtures for \Aws\ElasticTranscoder\ElasticTranscoderClient->readPreset.
    'readPreset' => array(
        'quality_low' => array(
            'System preset: HLS Video - 600k.' => array(
                'Preset' => array(
                    'Thumbnails' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '192',
                        'Format' => 'png',
                        'PaddingPolicy' => 'NoPad',
                        'Interval' => '300',
                        'MaxHeight' => '108',
                    ),
                    'Container' => 'ts',
                    'Description' => 'System preset: HLS Video - 600k',
                    'Video' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '480',
                        'PaddingPolicy' => 'NoPad',
                        'MaxFrameRate' => '60',
                        'FrameRate' => 'auto',
                        'MaxHeight' => '320',
                        'KeyframesMaxDist' => '90',
                        'FixedGOP' => 'true',
                        'Codec' => 'H.264',
                        'Watermarks' => array(
                            0 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopLeft',
                            ),
                            1 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopRight',
                            ),
                            2 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomLeft',
                            ),
                            3 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomRight',
                            ),
                        ),
                        'CodecOptions' => array(
                            'Profile' => 'baseline',
                            'MaxBitRate' => '472',
                            'InterlacedMode' => 'Progressive',
                            'Level' => '3',
                            'ColorSpaceConversionMode' => 'None',
                            'MaxReferenceFrames' => '3',
                            'BufferSize' => '4248',
                        ),
                        'BitRate' => '472',
                        'DisplayAspectRatio' => 'auto',
                    ),
                    'Type' => 'System',
                    'Id' => '1351620000001-200045',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-200045',
                    'Name' => 'System preset: HLS Video - 600k',
                ),
            ),
            'System preset: MPEG-Dash Video - 600k.' => array(
                'Preset' => array(
                    'Thumbnails' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '192',
                        'Format' => 'png',
                        'PaddingPolicy' => 'NoPad',
                        'Interval' => '300',
                        'MaxHeight' => '108',
                    ),
                    'Container' => 'fmp4',
                    'Description' => 'System preset: MPEG-Dash Video - 600k',
                    'Video' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '426',
                        'PaddingPolicy' => 'NoPad',
                        'FrameRate' => '30',
                        'MaxHeight' => '240',
                        'KeyframesMaxDist' => '60',
                        'FixedGOP' => 'true',
                        'Codec' => 'H.264',
                        'Watermarks' => array(
                            0 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopLeft',
                            ),
                            1 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopRight',
                            ),
                            2 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomLeft',
                            ),
                            3 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomRight',
                            ),
                        ),
                        'CodecOptions' => array(
                            'Profile' => 'main',
                            'MaxBitRate' => '600',
                            'InterlacedMode' => 'Progressive',
                            'Level' => '3',
                            'ColorSpaceConversionMode' => 'None',
                            'MaxReferenceFrames' => '1',
                            'BufferSize' => '1200',
                        ),
                        'BitRate' => '600',
                        'DisplayAspectRatio' => 'auto',
                    ),
                    'Type' => 'System',
                    'Id' => '1351620000001-500050',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-500050',
                    'Name' => 'System preset: MPEG-Dash Video - 600k',
                ),
            ),
            'System preset: HLS Audio - 160k' => array(
                'Preset' => array(
                    'Id' => '1351620000001-200060',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-200060',
                    'Name' => 'System preset: HLS Audio - 160k',
                    'Description' => 'System Preset: HLS Audio 160 kilobits/second',
                    'Container' => 'ts',
                    'Audio' => array(
                        'Codec' => 'AAC',
                        'SampleRate' => '44100',
                        'BitRate' => '160',
                        'Channels' => '2',
                        'CodecOptions' => array(
                            'Profile' => 'AAC-LC'
                        )
                    ),
                    'Type' => 'System'
                )
            ),
            'System preset: MPEG-Dash Audio - 128k' => array(
                'Preset' => array(
                    'Id' => '1351620000001-500060',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-500060',
                    'Name' => 'System preset: MPEG-Dash Audio - 128k',
                    'Description' => 'System preset: MPEG-Dash Audio - 128k',
                    'Container' => 'fmp4',
                    'Audio' => array(
                        'Codec' => 'AAC',
                        'SampleRate' => '48000',
                        'BitRate' => '128',
                        'Channels' => '2',
                        'CodecOptions' => array(
                            'Profile' => 'AAC-LC'
                        )
                    ),
                    'Type' => 'System'
                )
            )
        ),
        'quality_medium' => array(
            'System preset: HLS Video - 1M.' => array(
                'Preset' => array(
                    'Thumbnails' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '192',
                        'Format' => 'png',
                        'PaddingPolicy' => 'NoPad',
                        'Interval' => '300',
                        'MaxHeight' => '108',
                    ),
                    'Container' => 'ts',
                    'Description' => 'System preset: HLS Video - 1M',
                    'Video' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '640',
                        'PaddingPolicy' => 'NoPad',
                        'MaxFrameRate' => '60',
                        'FrameRate' => 'auto',
                        'MaxHeight' => '432',
                        'KeyframesMaxDist' => '90',
                        'FixedGOP' => 'true',
                        'Codec' => 'H.264',
                        'Watermarks' => array(
                            0 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopLeft',
                            ),
                            1 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopRight',
                            ),
                            2 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomLeft',
                            ),
                            3 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomRight',
                            ),
                        ),
                        'CodecOptions' => array(
                            'Profile' => 'main',
                            'MaxBitRate' => '872',
                            'InterlacedMode' => 'Progressive',
                            'Level' => '3',
                            'ColorSpaceConversionMode' => 'None',
                            'MaxReferenceFrames' => '3',
                            'BufferSize' => '7848',
                        ),
                        'BitRate' => '872',
                        'DisplayAspectRatio' => 'auto',
                    ),
                    'Type' => 'System',
                    'Id' => '1351620000001-200035',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-200035',
                    'Name' => 'System preset: HLS Video - 1M',
                ),
            ),
            'System preset: MPEG-Dash Video - 1.2M' => array(
                'Preset' => array(
                    'Thumbnails' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '192',
                        'Format' => 'png',
                        'PaddingPolicy' => 'NoPad',
                        'Interval' => '300',
                        'MaxHeight' => '108',
                    ),
                    'Container' => 'fmp4',
                    'Description' => 'System preset: MPEG-Dash Video - 1.2M',
                    'Video' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '640',
                        'PaddingPolicy' => 'NoPad',
                        'FrameRate' => '30',
                        'MaxHeight' => '360',
                        'KeyframesMaxDist' => '60',
                        'FixedGOP' => 'true',
                        'Codec' => 'H.264',
                        'Watermarks' => array(
                            0 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopLeft',
                            ),
                            1 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopRight',
                            ),
                            2 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomLeft',
                            ),
                            3 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomRight',
                            ),
                        ),
                        'CodecOptions' => array(
                            'Profile' => 'main',
                            'MaxBitRate' => '1200',
                            'InterlacedMode' => 'Progressive',
                            'Level' => '3',
                            'ColorSpaceConversionMode' => 'None',
                            'MaxReferenceFrames' => '3',
                            'BufferSize' => '2400',
                        ),
                        'BitRate' => '1200',
                        'DisplayAspectRatio' => 'auto',
                    ),
                    'Type' => 'System',
                    'Id' => '1351620000001-500040',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-500040',
                    'Name' => 'System preset: MPEG-Dash Video - 1.2M',
                ),
            ),
            'System preset: HLS Audio - 160k' => array(
                'Preset' => array(
                    'Id' => '1351620000001-200060',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-200060',
                    'Name' => 'System preset: HLS Audio - 160k',
                    'Description' => 'System Preset: HLS Audio 160 kilobits/second',
                    'Container' => 'ts',
                    'Audio' => array(
                        'Codec' => 'AAC',
                        'SampleRate' => '44100',
                        'BitRate' => '160',
                        'Channels' => '2',
                        'CodecOptions' => array(
                            'Profile' => 'AAC-LC'
                        )
                    ),
                    'Type' => 'System'
                )
            ),
            'System preset: MPEG-Dash Audio - 128k' => array(
                'Preset' => array(
                    'Id' => '1351620000001-500060',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-500060',
                    'Name' => 'System preset: MPEG-Dash Audio - 128k',
                    'Description' => 'System preset: MPEG-Dash Audio - 128k',
                    'Container' => 'fmp4',
                    'Audio' => array(
                        'Codec' => 'AAC',
                        'SampleRate' => '48000',
                        'BitRate' => '128',
                        'Channels' => '2',
                        'CodecOptions' => array(
                            'Profile' => 'AAC-LC'
                        )
                    ),
                    'Type' => 'System'
                )
            )
        ),
        'quality_high' => array(
            'System preset: HLS Video - 2M.' => array(
                'Preset' => array(
                    'Thumbnails' => array(
                            'SizingPolicy' => 'ShrinkToFit',
                            'MaxWidth' => '192',
                            'Format' => 'png',
                            'PaddingPolicy' => 'NoPad',
                            'Interval' => '300',
                            'MaxHeight' => '108',
                        ),
                    'Container' => 'ts',
                    'Description' => 'System preset: HLS Video - 2M',
                    'Video' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '1024',
                        'PaddingPolicy' => 'NoPad',
                        'MaxFrameRate' => '60',
                        'FrameRate' => 'auto',
                        'MaxHeight' => '768',
                        'KeyframesMaxDist' => '90',
                        'FixedGOP' => 'true',
                        'Codec' => 'H.264',
                        'Watermarks' => array(
                            0 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopLeft',
                            ),
                            1 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopRight',
                            ),
                            2 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomLeft',
                            ),
                            3 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomRight',
                            ),
                        ),
                        'CodecOptions' => array(
                            'Profile' => 'main',
                            'MaxBitRate' => '1872',
                            'InterlacedMode' => 'Progressive',
                            'Level' => '3.1',
                            'ColorSpaceConversionMode' => 'None',
                            'MaxReferenceFrames' => '3',
                            'BufferSize' => '16848',
                        ),
                        'BitRate' => '1872',
                        'DisplayAspectRatio' => 'auto',
                    ),
                    'Type' => 'System',
                    'Id' => '1351620000001-200015',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-200015',
                    'Name' => 'System preset: HLS Video - 2M',
                ),
            ),
            'System preset: MPEG-Dash Video - 2.4M.' => array(
                'Preset' => array(
                    'Thumbnails' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '192',
                        'Format' => 'png',
                        'PaddingPolicy' => 'NoPad',
                        'Interval' => '300',
                        'MaxHeight' => '108',
                    ),
                    'Container' => 'fmp4',
                    'Description' => 'System preset: MPEG-Dash Video - 2.4M',
                    'Video' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '854',
                        'PaddingPolicy' => 'NoPad',
                        'FrameRate' => '30',
                        'MaxHeight' => '480',
                        'KeyframesMaxDist' => '60',
                        'FixedGOP' => 'true',
                        'Codec' => 'H.264',
                        'Watermarks' => array(
                            0 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopLeft',
                            ),
                            1 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopRight',
                            ),
                            2 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomLeft',
                            ),
                            3 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomRight',
                            ),
                        ),
                        'CodecOptions' => array(
                            'Profile' => 'main',
                            'MaxBitRate' => '2400',
                            'InterlacedMode' => 'Progressive',
                            'Level' => '3.1',
                            'ColorSpaceConversionMode' => 'None',
                            'MaxReferenceFrames' => '3',
                            'BufferSize' => '4800',
                        ),
                        'BitRate' => '2400',
                        'DisplayAspectRatio' => 'auto',
                    ),
                    'Type' => 'System',
                    'Id' => '1351620000001-500030',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-500030',
                    'Name' => 'System preset: MPEG-Dash Video - 2.4M',
                ),
            ),
            'System preset: HLS Audio - 160k' => array(
                'Preset' => array(
                    'Id' => '1351620000001-200060',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-200060',
                    'Name' => 'System preset: HLS Audio - 160k',
                    'Description' => 'System Preset: HLS Audio 160 kilobits/second',
                    'Container' => 'ts',
                    'Audio' => array(
                        'Codec' => 'AAC',
                        'SampleRate' => '44100',
                        'BitRate' => '160',
                        'Channels' => '2',
                        'CodecOptions' => array(
                            'Profile' => 'AAC-LC'
                        )
                    ),
                    'Type' => 'System'
                )
            ),
            'System preset: MPEG-Dash Audio - 128k' => array(
                'Preset' => array(
                    'Id' => '1351620000001-500060',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-500060',
                    'Name' => 'System preset: MPEG-Dash Audio - 128k',
                    'Description' => 'System preset: MPEG-Dash Audio - 128k',
                    'Container' => 'fmp4',
                    'Audio' => array(
                        'Codec' => 'AAC',
                        'SampleRate' => '48000',
                        'BitRate' => '128',
                        'Channels' => '2',
                        'CodecOptions' => array(
                            'Profile' => 'AAC-LC'
                        )
                    ),
                    'Type' => 'System'
                )
            )
        ),
        'audio_output' => array(
            'System preset: Audio MP3 - 192 kilobits/second.' => array(
                'Preset' => array(
                    'Container' => 'mp3',
                    'Description' => 'System preset: Audio MP3 - 192 kilobits/second',
                    'Audio' => array(
                        'Channels' => '2',
                        'SampleRate' => '44100',
                        'Codec' => 'mp3',
                        'BitRate' => '192',
                    ),
                    'Type' => 'System',
                    'Id' => '1351620000001-300020',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-300020',
                    'Name' => 'System preset: Audio MP3 - 192k',
                ),
            ),
        ),
        'download_files' => array(
            'System preset: Facebook, SmugMug, Vimeo, YouTube.' => array(
                'Preset' => array(
                    'Thumbnails' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '192',
                        'Format' => 'png',
                        'PaddingPolicy' => 'NoPad',
                        'Interval' => '60',
                        'MaxHeight' => '108',
                    ),
                    'Container' => 'mp4',
                    'Description' => 'System preset: Facebook, SmugMug, Vimeo, YouTube',
                    'Video' => array(
                        'SizingPolicy' => 'ShrinkToFit',
                        'MaxWidth' => '1280',
                        'PaddingPolicy' => 'NoPad',
                        'FrameRate' => '30',
                        'MaxHeight' => '720',
                        'KeyframesMaxDist' => '90',
                        'FixedGOP' => 'false',
                        'Codec' => 'H.264',
                        'Watermarks' => array(
                            0 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopLeft',
                            ),
                            1 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Top',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'TopRight',
                            ),
                            2 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Left',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomLeft',
                            ),
                            3 => array(
                                'SizingPolicy' => 'ShrinkToFit',
                                'VerticalOffset' => '10%',
                                'VerticalAlign' => 'Bottom',
                                'Target' => 'Content',
                                'MaxWidth' => '10%',
                                'MaxHeight' => '10%',
                                'HorizontalAlign' => 'Right',
                                'HorizontalOffset' => '10%',
                                'Opacity' => '100',
                                'Id' => 'BottomRight',
                            ),
                        ),
                        'CodecOptions' => array(
                            'Profile' => 'main',
                            'MaxReferenceFrames' => '3',
                            'ColorSpaceConversionMode' => 'None',
                            'InterlacedMode' => 'Progressive',
                            'Level' => '3.1',
                        ),
                        'BitRate' => '2200',
                        'DisplayAspectRatio' => 'auto',
                    ),
                    'Audio' => array(
                        'Channels' => '2',
                        'CodecOptions' => array(
                            'Profile' => 'AAC-LC',
                        ),
                        'SampleRate' => '44100',
                        'Codec' => 'AAC',
                        'BitRate' => '160',
                    ),
                    'Type' => 'System',
                    'Id' => '1351620000001-100070',
                    'Arn' => 'arn:aws:elastictranscoder:ap-southeast-2:512561797349:preset/1351620000001-100070',
                    'Name' => 'System preset: Web',
                ),
            ),
        ),
    ),
    'hls_playlist_fixture' => <<<'EOF'
#EXTM3U
#EXT-X-I-FRAME-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=61831000,CODECS="avc1.4d001f",URI="http://moodle.local/pluginfile.php/1/local_smartmedia/media/0/13ed14cef757cd7797345cb76b30c3d83caf2513/conversions/1351620000001-200015_iframe.m3u8"
#EXT-X-I-FRAME-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=14654000,CODECS="avc1.42001e",URI="http://moodle.local/pluginfile.php/1/local_smartmedia/media/0/13ed14cef757cd7797345cb76b30c3d83caf2513/conversions/1351620000001-200045_iframe.m3u8"
#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=3624000,RESOLUTION=1024x576,CODECS="avc1.4d001f"
http://moodle.local/pluginfile.php/1/local_smartmedia/media/0/13ed14cef757cd7797345cb76b30c3d83caf2513/conversions/1351620000001-200015_v4.m3u8
#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=917000,RESOLUTION=480x270,CODECS="avc1.42001e"
http://moodle.local/pluginfile.php/1/local_smartmedia/media/0/13ed14cef757cd7797345cb76b30c3d83caf2513/conversions/1351620000001-200045_v4.m3u8
EOF
,
    'mpd_playlist_fixture' => <<<'EOF'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<MPD mediaPresentationDuration="PT9M56.434S" minBufferTime="PT1.500S" profiles="urn:mpeg:dash:profile:isoff-on-demand:2011" type="static" xmlns="urn:mpeg:dash:schema:mpd:2011">
    <Period duration="PT9M56.434S">
        <AdaptationSet segmentAlignment="true" subsegmentAlignment="true" subsegmentStartsWithSAP="1">
            <Representation bandwidth="2324427" codecs="avc1.4d401f" frameRate="30" height="480" id="VIDEO-1" mimeType="video/mp4" startWithSAP="1" width="854">
                <BaseURL>http://moodle.local/pluginfile.php/1/local_smartmedia/media/0/13ed14cef757cd7797345cb76b30c3d83caf2513/conversions/1351620000001-500030.fmp4</BaseURL>
                <SegmentBase indexRange="918-4525" indexRangeExact="true">
                    <Initialization range="0-917"/>
                </SegmentBase>
            </Representation>
            <Representation bandwidth="596014" codecs="avc1.4d401e" frameRate="30" height="240" id="VIDEO-2" mimeType="video/mp4" startWithSAP="1" width="426">
                <BaseURL>http://moodle.local/pluginfile.php/1/local_smartmedia/media/0/13ed14cef757cd7797345cb76b30c3d83caf2513/conversions/1351620000001-500050.fmp4</BaseURL>
                <SegmentBase indexRange="915-4522" indexRangeExact="true">
                    <Initialization range="0-914"/>
                </SegmentBase>
            </Representation>
        </AdaptationSet>
    </Period>
</MPD>
EOF
,
    'list_object_fixture' => array (
            'IsTruncated' => false,
            'Marker' => '',
            'Contents' =>
            array (
                0 =>
                array (
                    'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200015.ts',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 06:00:07.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"6304bde53e26a990de46e5f32fdfd9e4-30"',
                    'Size' => 152357832,
                    'StorageClass' => 'STANDARD',
                ),
                1 =>
                array (
                    'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200015_iframe.m3u8',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 06:00:07.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"0527f78ad12fbb7a432b057ef748b546"',
                    'Size' => 22272,
                    'StorageClass' => 'STANDARD',
                ),
                2 =>
                array (
                    'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200015_v4.m3u8',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 06:00:07.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"fccb48099daf60810b9656078223f9cb"',
                    'Size' => 22106,
                    'StorageClass' => 'STANDARD',
                ),
                3 =>
                array (
                    'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200045.ts',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 05:59:05.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"2f1a6c4cb5aca2091ca16bd5c0fc2d9d-8"',
                    'Size' => 39797720,
                    'StorageClass' => 'STANDARD',
                ),
                4 =>
                array (
                    'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200045_iframe.m3u8',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 05:59:05.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"621f6ef34106b01b2adfde18b683a9b1"',
                    'Size' => 22014,
                    'StorageClass' => 'STANDARD',
                ),
                5 =>
                array (
                    'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-200045_v4.m3u8',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 05:59:05.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"dc9d6f905c879e81382e3b52b414ad58"',
                    'Size' => 21972,
                    'StorageClass' => 'STANDARD',
                ),
                6 =>
                array (
                    'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-500030.fmp4',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 05:59:30.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"18f83f0470d00a4dc147f9e528f2acbe-34"',
                    'Size' => 173364464,
                    'StorageClass' => 'STANDARD',
                ),
                7 =>
                array (
                    'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/8f3d12e28ecb231852436d5c905d2a3e6ee8e119_1351620000001-500050.fmp4',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 05:58:53.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"56474db1f378253d7366ced488d079bf-9"',
                    'Size' => 44246294,
                    'StorageClass' => 'STANDARD',
                ),
                8 =>
                array (
                    'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/8f3d12e28ecb231852436d5c905d2a3e6ee8e119_hls_playlist.m3u8',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 06:00:10.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"b85a5b40a3a7213d291a681ce607a8e2"',
                    'Size' => 644,
                    'StorageClass' => 'STANDARD',
                ),
                9 =>
                array (
                    'Key' => '8f3d12e28ecb231852436d5c905d2a3e6ee8e119/conversions/8f3d12e28ecb231852436d5c905d2a3e6ee8e119_mpegdash_playlist.mpd',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 06:00:10.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"3713d3a07d9c0fc8abadaaf700a0bc5a"',
                    'Size' => 1315,
                    'StorageClass' => 'STANDARD',
                ),
                10 =>
                array (
                    'Key' => '8f3d12e28ecb232252436d5c905d2a3e6ee8e119/conversions8f3d12e28ecb232252436d5c905d2a3e6ee8e119_mpegdash_playlist.mpd',
                    'LastModified' =>
                    Aws\Api\DateTimeResult::__set_state(array(
                        'date' => '2019-11-02 06:00:10.000000',
                        'timezone_type' => 2,
                        'timezone' => 'Z',
                    )),
                    'ETag' => '"3713d3a07d9c0fc8abadaaf700a0bc5a"',
                    'Size' => 1315,
                    'StorageClass' => 'STANDARD',
                ),
            ),
            'Name' => 'smt1566767930-output',
            'Prefix' => '',
            'MaxKeys' => 1000,
            'EncodingType' => 'url',
            '@metadata' =>
            array (
                'statusCode' => 200,
                'effectiveUri' => 'https://smt1566767930-output.s3.ap-southeast-2.amazonaws.com/?encoding-type=url',
                'headers' =>
                array (
                    'x-amz-id-2' => 'h6hZsXGa7LQKpnwtKNouecZk8xQ4q3r+eCd6lGZYa5bK+ybEmCbxbMPW8InqZPlMLN17QeqReOc=',
                    'x-amz-request-id' => '3AA57C7437D3A143',
                    'date' => 'Sat, 02 Nov 2019 06:06:12 GMT',
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
        )
);
