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
}
