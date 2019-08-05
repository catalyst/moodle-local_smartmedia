# Smart Media #

Smart media aims to enhance Moodle's processing and delivery of multimedia while simplifying the process of managing multimedia for teachers and students.

Smart media leverages cloud services provided through Amazon Web Services (AWS) in order to conduct video transcoding into required formats and provide additional analytics functionality for multimedia, initially support has not be provided for other cloud service providers.

## Installation ##

1. Install dependency plugin local/aws. See [local/aws](##local/aws)
2. Install dependency binary FFmpeg on your Moodle server. See [FFmpeg](##FFmpeg)
3. Clone the plugin git repo into your Moodle codebase root `git clone git@github.com:catalyst/moodle-local_smartmedia.git local/smartmedia`
4. Update plugin settings. See [Settings](##Settings)

## local/aws

The local/aws plugin is a dependency for local/smartmedia, and provides the AWS PHP SDK that is required in order for Moodle to utilise AWS cloud services.

You can download this from the Moodle plugin library at <https://moodle.org/plugins/local_aws>

Most simply, you can install this by cloning the git repo into your Moodle codebase:
```bash
git clone https://github.com/catalyst/moodle-local_aws local/aws
```

More detailed information is available at <https://github.com/catalyst/moodle-local_aws> in the README

## FFmpeg

FFmpeg is an open source multimedia framework and provides the multimedia stream analyser `ffprobe`, facilitating the collection of metadata for multimedia. 

This metadata is utilised to present reports on what media is actually in your Moodle instance.

FFmpeg is a requirement for running the local/smartmedia scheduled tasks.

To install FFmpeg, if your using a Debian based Linux distribution:
```bash
sudo apt-get update
sudo apt install ffmpeg
```
For Windows based or any other servers follow the instructions and link at <https://ffmpeg.org/download.html>

You can get more info about FFmpeg at <https://ffmpeg.org/>

## Settings

Once the local/smartmedia plugin is installed, double check the installation location of `ffprobe` and ensure that you set this in Moodle settings under `Site Administration > Plugins > Local plugins > Smart Media` for the *FFProbe binary path* field.
```bash
whereis ffprobe # default /usr/bin/ffprobe
```

## Additional Information
The following sections provide an overview of some additional topics for this plugin and it's associated AWS architecture.

### Conversion Architecture
The below image shows the high level architecture the plugin provisioning process sets up in AWS.

![Conversion Architecture](/pix/SmartMediaAWSArch.png?raw=true)

## License ##

2019 Catalyst IT Australia

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.


This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

<img alt="Catalyst IT" src="https://raw.githubusercontent.com/catalyst/moodle-local_smartmedia/master/pix/catalyst-logo.png" width="400">