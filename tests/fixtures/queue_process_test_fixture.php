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
    'sqsmessages' => array (
        'Messages' =>
        array (
            0 =>
            array (
                'MessageId' => 'b804a5e3-0086-4da6-be10-966ebf6083ea',
                'ReceiptHandle' => 'AQEBnlfKQ/tFaDG50CiVG9VQZ9fjxdK/g7qaucabJFCerdBDdRhbRIOkcD7/le6tE90WALCIkrsbGkJ1+2B9hqaScMpvw113xL1NkyVrCxrK3g7a7f938IbuID2dLiZ/gzUmAIG2DAV3ijZbQXs+/KfkeRlRE6gkv8pqW768VziNbA2O9efAnzBajnBJQUmQGnnVnThZeDf/Yh34GRqeu1d3oC4aLQD6dx8xjkbWJKNYoFPyKfsZG2miuAAEdXxWNBvVNsBquH3U9FmxLuYjWrBBhPlAaMPySahvKyCYDlZ3WOihEhivQ0VOrRz+bsZWJyR+KqpywJu8/+62myNjapxms0BBceCRkc9NtlZ+oePkeDUaZblPZsxNjBCm4/aD0YGd6KTXHGOoIHF2Fs1kI7muU4V2iDspBz9+jBGNz+3wHrM=',
                'MD5OfBody' => '49e4e0a1b2a24b669cef908c8e3be862',
                'Body' => '{"siteid": "wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local", "objectkey": "SampleVideo1mb", "process": "StartFaceDetection", "status": "SUCCEEDED", "message": {"JobId": "70626c046c847548d6c3e3ef42d8e2755be54ec9ad3d7350620c6c68239975a3", "Status": "SUCCEEDED", "API": "StartFaceDetection", "JobTag": "1566091572480-6zh1ov", "Timestamp": 1566091614776, "Video": {"S3ObjectName": "SampleVideo1mb/conversions/SampleVideo1mb.mp4", "S3Bucket": "smt1566089892-output"}}, "timestamp": 1566091793}',
                'Attributes' =>
                array (
                    'SenderId' => 'AROA2C7YAPAQNI2XBZMTN:smt1566089892_rekognition_complete',
                    'ApproximateFirstReceiveTimestamp' => '1566091793847',
                    'ApproximateReceiveCount' => '4',
                    'SentTimestamp' => '1566091793847',
                ),
                'MD5OfMessageAttributes' => '58695f7626857aca927f4b4281ca4ae5',
                'MessageAttributes' =>
                array (
                    'inputkey' =>
                    array (
                        'StringValue' => 'SampleVideo1mb',
                        'DataType' => 'String',
                    ),
                    'siteid' =>
                    array (
                        'StringValue' => 'wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local',
                        'DataType' => 'String',
                    ),
                ),
            ),
            1 =>
            array (
                'MessageId' => 'ecd44ebd-d5b6-4b6f-bda6-f9374995c3ac',
                'ReceiptHandle' => 'AQEBPWXP6D/gDYUNKrZtSMwWN9tvH/AkANg+OQKcY71YTC6RGenmm9AspWF4zJYOZA+PIwOTfoqL00iPzGCtF2ybPvcHtypRWGQ3nsR8tWR2EPNDURYCWxmvqaSQWh0tUHi9iakJewicFjsy+JOQnbI8yiq88AqhksWQzRrvgoGglUIpbyBPmSuK2wqpd5IDS4VGrMdBjlwr+VkBrCHCJs350Kl5MZw8cNPqN/YX3Mds3rKcQBKOytuMj5QP8ejW781q79nVyu9SyaDVOh3KPGSpmeUQ7upNlIRn3x12pJ4RTPZSAu1/ifiqt4je0yP9umgT16M7fzBT0p3y1Rk0RVDw8+AJ2I5FkJ19jKvkdDDDJyb5sXDFlLgIom/R94honk0bTdJSRSIiF1GB2FFCrFIwN+89pkYkKN3JvPvZXTOrXho=',
                'MD5OfBody' => 'd2fa266e5c1f2e16dee1ff344cec4aec',
                'Body' => '{"siteid": "wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local", "objectkey": "SampleVideo1mb", "process": "StartLabelDetection", "status": "SUCCEEDED", "message": {"JobId": "40169f69f27719a7966c44e3b9c6bb72a064dddb50edc8ab459520283623fddc", "Status": "SUCCEEDED", "API": "StartLabelDetection", "JobTag": "1566091572480-6zh1ov", "Timestamp": 1566091618782, "Video": {"S3ObjectName": "SampleVideo1mb/conversions/SampleVideo1mb.mp4", "S3Bucket": "smt1566089892-output"}}, "timestamp": 1566091817}',
                'Attributes' =>
                array (
                    'SenderId' => 'AROA2C7YAPAQNI2XBZMTN:smt1566089892_rekognition_complete',
                    'ApproximateFirstReceiveTimestamp' => '1566091817238',
                    'ApproximateReceiveCount' => '5',
                    'SentTimestamp' => '1566091817238',
                ),
                'MD5OfMessageAttributes' => '58695f7626857aca927f4b4281ca4ae5',
                'MessageAttributes' =>
                array (
                    'inputkey' =>
                    array (
                        'StringValue' => 'SampleVideo1mb',
                        'DataType' => 'String',
                    ),
                    'siteid' =>
                    array (
                        'StringValue' => 'wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local',
                        'DataType' => 'String',
                    ),
                ),
            ),
            2 =>
            array (
                'MessageId' => 'ecd44ebd-d5b6-4b6f-bda6-f9374995c3ac',
                'ReceiptHandle' => 'AQEBPWXP6D/gDYUNKrZtSMwWN9tvH/AkANg+OQKcY71YTC6RGenmm9AspWF4zJYOZA+PIwOTfoqL00iPzGCtF2ybPvcHtypRWGQ3nsR8tWR2EPNDURYCWxmvqaSQWh0tUHi9iakJewicFjsy+JOQnbI8yiq88AqhksWQzRrvgoGglUIpbyBPmSuK2wqpd5IDS4VGrMdBjlwr+VkBrCHCJs350Kl5MZw8cNPqN/YX3Mds3rKcQBKOytuMj5QP8ejW781q79nVyu9SyaDVOh3KPGSpmeUQ7upNlIRn3x12pJ4RTPZSAu1/ifiqt4je0yP9umgT16M7fzBT0p3y1Rk0RVDw8+AJ2I5FkJ19jKvkdDDDJyb5sXDFlLgIom/R94honk0bTdJSRSIiF1GB2FFCrFIwN+89pkYkKN3JvPvZXTOrXho=',
                'MD5OfBody' => 'd2fa266e5c1f2e16dee1ff344cec4aec',
                'Body' => '{"siteid": "wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local", "objectkey": "SampleVideo1mb", "process": "StartLabelDetection", "status": "SUCCEEDED", "message": {"JobId": "40169f69f27719a7966c44e3b9c6bb72a064dddb50edc8ab459520283623fddc", "Status": "SUCCEEDED", "API": "StartLabelDetection", "JobTag": "1566091572480-6zh1ov", "Timestamp": 1566091618782, "Video": {"S3ObjectName": "SampleVideo1mb/conversions/SampleVideo1mb.mp4", "S3Bucket": "smt1566089892-output"}}, "timestamp": 1566091817}',
                'Attributes' =>
                array (
                    'SenderId' => 'AROA2C7YAPAQNI2XBZMTN:smt1566089892_rekognition_complete',
                    'ApproximateFirstReceiveTimestamp' => '1566091817238',
                    'ApproximateReceiveCount' => '5',
                    'SentTimestamp' => '1566091817238',
                ),
                'MD5OfMessageAttributes' => '58695f7626857aca927f4b4281ca4ae5',
                'MessageAttributes' =>
                array (
                    'inputkey' =>
                    array (
                        'StringValue' => 'SampleVideo1mb',
                        'DataType' => 'String',
                    ),
                    'siteid' =>
                    array (
                        'StringValue' => 'wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local',
                        'DataType' => 'String',
                    ),
                ),
            ),
        ),
        '@metadata' =>
        array (
            'statusCode' => 200,
            'effectiveUri' => 'https://sqs.ap-southeast-2.amazonaws.com/693620471840/smt1566089892-SmartmediaSqsQueue',
            'headers' =>
            array (
                'x-amzn-requestid' => 'd096e166-df7a-524c-9631-b4b403274e1c',
                'date' => 'Sun, 18 Aug 2019 04:54:53 GMT',
                'content-type' => 'text/xml',
                'content-length' => '4442',
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
    'receviedmessages' => array (
        'b804a5e3-0086-4da6-be10-966ebf6083ea' =>
        array (
            'MessageId' => 'b804a5e3-0086-4da6-be10-966ebf6083ea',
            'ReceiptHandle' => 'AQEBnlfKQ/tFaDG50CiVG9VQZ9fjxdK/g7qaucabJFCerdBDdRhbRIOkcD7/le6tE90WALCIkrsbGkJ1+2B9hqaScMpvw113xL1NkyVrCxrK3g7a7f938IbuID2dLiZ/gzUmAIG2DAV3ijZbQXs+/KfkeRlRE6gkv8pqW768VziNbA2O9efAnzBajnBJQUmQGnnVnThZeDf/Yh34GRqeu1d3oC4aLQD6dx8xjkbWJKNYoFPyKfsZG2miuAAEdXxWNBvVNsBquH3U9FmxLuYjWrBBhPlAaMPySahvKyCYDlZ3WOihEhivQ0VOrRz+bsZWJyR+KqpywJu8/+62myNjapxms0BBceCRkc9NtlZ+oePkeDUaZblPZsxNjBCm4/aD0YGd6KTXHGOoIHF2Fs1kI7muU4V2iDspBz9+jBGNz+3wHrM=',
            'MD5OfBody' => '49e4e0a1b2a24b669cef908c8e3be862',
            'Body' => '{"siteid": "wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local", "objectkey": "SampleVideo1mb", "process": "StartFaceDetection", "status": "SUCCEEDED", "message": {"JobId": "70626c046c847548d6c3e3ef42d8e2755be54ec9ad3d7350620c6c68239975a3", "Status": "SUCCEEDED", "API": "StartFaceDetection", "JobTag": "1566091572480-6zh1ov", "Timestamp": 1566091614776, "Video": {"S3ObjectName": "SampleVideo1mb/conversions/SampleVideo1mb.mp4", "S3Bucket": "smt1566089892-output"}}, "timestamp": 1566091793}',
            'Attributes' =>
            array (
                'SenderId' => 'AROA2C7YAPAQNI2XBZMTN:smt1566089892_rekognition_complete',
                'ApproximateFirstReceiveTimestamp' => '1566091793847',
                'ApproximateReceiveCount' => '4',
                'SentTimestamp' => '1566091793847',
            ),
            'MD5OfMessageAttributes' => '58695f7626857aca927f4b4281ca4ae5',
            'MessageAttributes' =>
            array (
                'inputkey' =>
                array (
                    'StringValue' => 'SampleVideo1mb',
                    'DataType' => 'String',
                ),
                'siteid' =>
                array (
                    'StringValue' => 'wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local',
                    'DataType' => 'String',
                ),
            ),
        ),
        'ecd44ebd-d5b6-4b6f-bda6-f9374995c3ac' =>
        array (
            'MessageId' => 'ecd44ebd-d5b6-4b6f-bda6-f9374995c3ac',
            'ReceiptHandle' => 'AQEBPWXP6D/gDYUNKrZtSMwWN9tvH/AkANg+OQKcY71YTC6RGenmm9AspWF4zJYOZA+PIwOTfoqL00iPzGCtF2ybPvcHtypRWGQ3nsR8tWR2EPNDURYCWxmvqaSQWh0tUHi9iakJewicFjsy+JOQnbI8yiq88AqhksWQzRrvgoGglUIpbyBPmSuK2wqpd5IDS4VGrMdBjlwr+VkBrCHCJs350Kl5MZw8cNPqN/YX3Mds3rKcQBKOytuMj5QP8ejW781q79nVyu9SyaDVOh3KPGSpmeUQ7upNlIRn3x12pJ4RTPZSAu1/ifiqt4je0yP9umgT16M7fzBT0p3y1Rk0RVDw8+AJ2I5FkJ19jKvkdDDDJyb5sXDFlLgIom/R94honk0bTdJSRSIiF1GB2FFCrFIwN+89pkYkKN3JvPvZXTOrXho=',
            'MD5OfBody' => 'd2fa266e5c1f2e16dee1ff344cec4aec',
            'Body' => '{"siteid": "wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local", "objectkey": "SampleVideo1mb", "process": "StartLabelDetection", "status": "SUCCEEDED", "message": {"JobId": "40169f69f27719a7966c44e3b9c6bb72a064dddb50edc8ab459520283623fddc", "Status": "SUCCEEDED", "API": "StartLabelDetection", "JobTag": "1566091572480-6zh1ov", "Timestamp": 1566091618782, "Video": {"S3ObjectName": "SampleVideo1mb/conversions/SampleVideo1mb.mp4", "S3Bucket": "smt1566089892-output"}}, "timestamp": 1566091817}',
            'Attributes' =>
            array (
                'SenderId' => 'AROA2C7YAPAQNI2XBZMTN:smt1566089892_rekognition_complete',
                'ApproximateFirstReceiveTimestamp' => '1566091817238',
                'ApproximateReceiveCount' => '5',
                'SentTimestamp' => '1566091817238',
            ),
            'MD5OfMessageAttributes' => '58695f7626857aca927f4b4281ca4ae5',
            'MessageAttributes' =>
            array (
                'inputkey' =>
                array (
                    'StringValue' => 'SampleVideo1mb',
                    'DataType' => 'String',
                ),
                'siteid' =>
                array (
                    'StringValue' => 'wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local',
                    'DataType' => 'String',
                ),
            ),
        ),
        'ecd44ebd-d5b6-4b6f-bda6-f9374995c3dd' =>
            array (
                    'MessageId' => 'ecd44ebd-d5b6-4b6f-bda6-f9374995c3ac',
                    'ReceiptHandle' => 'AQEBPWXP6D/gDYUNKrZtSMwWN9tvH/AkANg+OQKcY71YTC6RGenmm9AspWF4zJYOZA+PIwOTfoqL00iPzGCtF2ybPvcHtypRWGQ3nsR8tWR2EPNDURYCWxmvqaSQWh0tUHi9iakJewicFjsy+JOQnbI8yiq88AqhksWQzRrvgoGglUIpbyBPmSuK2wqpd5IDS4VGrMdBjlwr+VkBrCHCJs350Kl5MZw8cNPqN/YX3Mds3rKcQBKOytuMj5QP8ejW781q79nVyu9SyaDVOh3KPGSpmeUQ7upNlIRn3x12pJ4RTPZSAu1/ifiqt4je0yP9umgT16M7fzBT0p3y1Rk0RVDw8+AJ2I5FkJ19jKvkdDDDJyb5sXDFlLgIom/R94honk0bTdJSRSIiF1GB2FFCrFIwN+89pkYkKN3JvPvZXTOrXho=',
                    'MD5OfBody' => 'd2fa266e5c1f2e16dee1ff344cec4aec',
                    'Body' => '{"siteid": "wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local", "objectkey": "SampleVideo1mb", "process": "StartLabelDetection", "status": "SUCCEEDED", "message": {"JobId": "40169f69f27719a7966c44e3b9c6bb72a064dddb50edc8ab459520283623fddc", "Status": "SUCCEEDED", "API": "StartLabelDetection", "JobTag": "1566091572480-6zh1ov", "Timestamp": 1566091618782, "Video": {"S3ObjectName": "SampleVideo1mb/conversions/SampleVideo1mb.mp4", "S3Bucket": "smt1566089892-output"}}, "timestamp": 1566091817}',
                    'Attributes' =>
                    array (
                            'SenderId' => 'AROA2C7YAPAQNI2XBZMTN:smt1566089892_rekognition_complete',
                            'ApproximateFirstReceiveTimestamp' => '1566091817238',
                            'ApproximateReceiveCount' => '5',
                            'SentTimestamp' => '1566091817238',
                    ),
                    'MD5OfMessageAttributes' => '58695f7626857aca927f4b4281ca4ae5',
                    'MessageAttributes' =>
                    array (
                            'inputkey' =>
                            array (
                                    'StringValue' => 'SampleVideo1mb',
                                    'DataType' => 'String',
                            ),
                            'siteid' =>
                            array (
                                    'StringValue' => 'wck1bOkID2Nj6mCG3bsQqUwxPz54eQaxmoodle.local',
                                    'DataType' => 'String',
                            ),
                    ),
            ),
    )
);
