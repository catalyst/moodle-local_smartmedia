<?php

namespace local_smartmedia;

class media_convert_preset {
    private readonly array $apidata;

    // TODO later replace with properly named get name.
    public function get_id(): string {
        return $this->apidata["Name"];
    }

    public function get_container(): string {
        return $this->apidata["Settings"]["ContainerSettings"]["Container"];
    }

    public function __construct(array $apidata) {
        $this->apidata = $apidata;
    }

    public function is_output_standard_definition() {
        if ($this->is_output_audio()) {
            return false;
        }
        return $this->apidata['Settings']['VideoDescription']['Height'] > LOCAL_SMARTMEDIA_MINIMUM_SD_HEIGHT;
    }

    public function is_output_high_definition() {
        if ($this->is_output_audio()) {
            return false;
        }
        return $this->apidata['Settings']['VideoDescription']['Height'] > LOCAL_SMARTMEDIA_MINIMUM_HD_HEIGHT;
    }

    public function is_output_audio() {
        return empty($this->apidata['Settings']['VideoDescription']);
    }

    public function is_output_video() {
        return !$this->is_output_audio();
    }

    public function is_input_video($height) {
        // MediaConvert doesn't make any distinctions between audio and video.
        // lets just say for now that everything is a video.
        // We ultimately should re-write this entire plugin to work properly.
        return true;
    }
}
