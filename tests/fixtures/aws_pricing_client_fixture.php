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
 * Test fixture for \Aws\Pricing\PricingClient method calls where params include 'ServiceCode' => AmazonETS.
 *
 * @package     local_smartmedia
 * @author      Tom Dickman <tomdickman@catalyst-au.net>
 * @copyright   2019 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

return [
    // Fixture for \Aws\Pricing\PricingClient->getAttributeValues.
    'getAttributeValues' => [
        'productFamily' =>
            '{
                "AttributeValues":[
                    {"Value":"High Definition"},
                    {"Value":"Audio"},
                    {"Value":"Standard Definition"}
                ]
            }',
        'transcodingResult' =>
            '{
                "AttributeValues":[
                    {"Value":"Error"},
                    {"Value":"Success"}
                ]
            }',
        'serviceCode' =>
            '{
                "AttributeValues":[
                    {"Value":"AmazonETS"}
                ]
            }',
        'termType' =>
            '{
                "AttributeValues":[
                    {"Value":"OnDemand"}
                ]
            }',
        'usageType' =>
            '{
                "AttributeValues":[
                    {"Value":"APN1-ets-audio-error"},
                    {"Value":"APN1-ets-audio-success"},
                    {"Value":"APN1-ets-hd-error"},
                    {"Value":"APN1-ets-hd-success"},
                    {"Value":"APN1-ets-sd-error"},
                    {"Value":"APN1-ets-sd-success"},
                    {"Value":"APS1-ets-audio-error"},
                    {"Value":"APS1-ets-audio-success"},
                    {"Value":"APS1-ets-hd-error"},
                    {"Value":"APS1-ets-hd-success"},
                    {"Value":"APS1-ets-sd-error"},
                    {"Value":"APS1-ets-sd-success"},
                    {"Value":"APS2-ets-audio-error"},
                    {"Value":"APS2-ets-audio-success"},
                    {"Value":"APS2-ets-hd-error"},
                    {"Value":"APS2-ets-hd-success"},
                    {"Value":"APS2-ets-sd-error"},
                    {"Value":"APS2-ets-sd-success"},
                    {"Value":"APS3-ets-audio-error"},
                    {"Value":"APS3-ets-audio-success"},
                    {"Value":"APS3-ets-hd-error"},
                    {"Value":"APS3-ets-hd-success"},
                    {"Value":"APS3-ets-sd-error"},
                    {"Value":"APS3-ets-sd-success"},
                    {"Value":"EU-ets-audio-error"},
                    {"Value":"EU-ets-audio-success"},
                    {"Value":"EU-ets-hd-error"},
                    {"Value":"EU-ets-hd-success"},
                    {"Value":"EU-ets-sd-error"},
                    {"Value":"EU-ets-sd-success"},
                    {"Value":"Global-ets-hd-success"},
                    {"Value":"Global-ets-sd-success"},
                    {"Value":"USW1-ets-audio-error"},
                    {"Value":"USW1-ets-audio-success"},
                    {"Value":"USW1-ets-hd-error"},
                    {"Value":"USW1-ets-hd-success"},
                    {"Value":"USW1-ets-sd-error"},
                    {"Value":"USW1-ets-sd-success"},
                    {"Value":"USW2-ets-audio-error"},
                    {"Value":"USW2-ets-audio-success"},
                    {"Value":"USW2-ets-hd-error"},
                    {"Value":"USW2-ets-hd-success"},
                    {"Value":"USW2-ets-sd-error"},
                    {"Value":"USW2-ets-sd-success"},
                    {"Value":"ets-audio-error"},
                    {"Value":"ets-audio-success"},
                    {"Value":"ets-hd-error"},
                    {"Value":"ets-hd-success"},
                    {"Value":"ets-sd-error"},
                    {"Value":"ets-sd-success"},
                    {"Value":"global-ets-audio-success"}
                ]
            }',
        'locationType' =>
            '{
                "AttributeValues":[
                    {"Value":"AWS Region"}
                ]
            }',
        'location' =>
            '{
                "AttributeValues":[
                    {"Value":"Any"},
                    {"Value":"Asia Pacific (Mumbai)"},
                    {"Value":"Asia Pacific (Singapore)"},
                    {"Value":"Asia Pacific (Sydney)"},
                    {"Value":"Asia Pacific (Tokyo)"},
                    {"Value":"EU (Ireland)"},
                    {"Value":"US East (N. Virginia)"},
                    {"Value":"US West (N. California)"},
                    {"Value":"US West (Oregon)"}
                ]
            }',
        'videoResolution' =>
            '{
                "AttributeValues":[
                    {"Value":"720p and above"},
                    {"Value":"Audio only"},
                    {"Value":"Less than 720p"}
                ]
            }'
    ],
    // Fixture for \Aws\Pricing\PricingClient->describeServices.
    'describeServices' => [
        'Services' => [
            '{
                "ServiceCode":"AmazonETS",
                "AttributeNames":[
                    "productFamily",
                    "transcodingResult",
                    "serviceCode",
                    "termType",
                    "usageType",
                    "locationType",
                    "location",
                    "videoResolution"
                ]
            }'
        ],
        'FormatVersion' => 'aws_v1',
        '@metadata' => [
            'statusCode' => 200,
            'effectiveUri' => 'https://api.pricing.us-east-1.amazonaws.com',
        ],
    ],
    // Fixture for \Aws\Pricing\PricingClient->getProducts.
    'getProducts' => [
        'FormatVersion' => 'aws_v1',
        'PriceList' => [
            '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS1-ets-audio-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Singapore)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"2M7EQWCEEZSJ69U7"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "2M7EQWCEEZSJ69U7.JRTCKXETXF":{
                            "priceDimensions":{
                                "2M7EQWCEEZSJ69U7.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Singapore) $0.00522 per minute for audio-only.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"2M7EQWCEEZSJ69U7.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0052200000"
                                    }
                                }
                            },
                            "sku":"2M7EQWCEEZSJ69U7",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
            '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS3-ets-audio-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Mumbai)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"2N4MPTUXKPSSU7VJ"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "2N4MPTUXKPSSU7VJ.JRTCKXETXF":{
                            "priceDimensions":{
                                "2N4MPTUXKPSSU7VJ.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Mumbai) $0.0045 per minute for audio-only.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"2N4MPTUXKPSSU7VJ.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0045000000"
                                    }
                                }
                            },
                            "sku":"2N4MPTUXKPSSU7VJ",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
            '{
                "product":{
                "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW2-ets-audio-success",
                        "locationType":"AWS Region",
                        "location":"US West (Oregon)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"2PS5CWVUXYHZC42Q"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "2PS5CWVUXYHZC42Q.JRTCKXETXF":{
                            "priceDimensions":{
                                "2PS5CWVUXYHZC42Q.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (Oregon) $0.0045 per minute for audio-only.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"2PS5CWVUXYHZC42Q.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0045000000"
                                    }
                                }
                            },
                            "sku":"2PS5CWVUXYHZC42Q",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
            '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"ets-hd-error",
                        "locationType":"AWS Region",
                        "location":"US East (N. Virginia)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"2WCD8HSZSBUVUN4K"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "2WCD8HSZSBUVUN4K.JRTCKXETXF":{
                            "priceDimensions":{
                                "2WCD8HSZSBUVUN4K.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US East (N. Virginia) $0.000 for HD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"2WCD8HSZSBUVUN4K.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"2WCD8HSZSBUVUN4K",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
            '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                    "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"ets-sd-error",
                        "locationType":"AWS Region",
                        "location":"US East (N. Virginia)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"42TGA2CPGV274QR2"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "42TGA2CPGV274QR2.JRTCKXETXF":{
                            "priceDimensions":{
                                "42TGA2CPGV274QR2.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US East (N. Virginia) $0.000 for SD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"42TGA2CPGV274QR2.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"42TGA2CPGV274QR2",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
            '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS2-ets-sd-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Sydney)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"4JD3VGHAHQ6QWPVT"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "4JD3VGHAHQ6QWPVT.JRTCKXETXF":{
                            "priceDimensions":{
                                "4JD3VGHAHQ6QWPVT.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Sydney) $0.017 per minute for SD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"4JD3VGHAHQ6QWPVT.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0170000000"
                                    }
                                }
                            },
                            "sku":"4JD3VGHAHQ6QWPVT",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
            '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APN1-ets-audio-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Tokyo)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"4UPKWMT4XKGSPXTA"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "4UPKWMT4XKGSPXTA.JRTCKXETXF":{
                            "priceDimensions":{
                                "4UPKWMT4XKGSPXTA.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Tokyo) audio that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"4UPKWMT4XKGSPXTA.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"4UPKWMT4XKGSPXTA",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
            '{
                "product":{
                    "productFamily":"Standard Definition",
                     "attributes":{
                       "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"Global-ets-sd-success",
                        "locationType":"AWS Region",
                        "location":"Any",
                        "videoResolution":"Less than 720p",
                        "operation":""
                     },
                     "sku":"5FYNDTBJGFNQ8E2F"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "5FYNDTBJGFNQ8E2F.A429C66SYZ":{
                            "priceDimensions":{
                                "5FYNDTBJGFNQ8E2F.A429C66SYZ.HC9A29KZQM":{
                                    "unit":"minutes",
                                    "endRange":"20",
                                    "description":"Global Free Tier for SD",
                                    "appliesTo":[
                                        "6KNNYA363QH2R346",
                                        "C2JR2Y4SZXFYKW7B",
                                        "A4R97WC2EFV3B72Q",
                                        "VW6FJWRWVMBC5TBY",
                                        "PBJ28CAUYT3C3APY",
                                        "7H54P7A8XSKKDVGF",
                                        "V35CETBNGB4SQT2W",
                                        "4JD3VGHAHQ6QWPVT"
                                    ],
                                    "rateCode":"5FYNDTBJGFNQ8E2F.A429C66SYZ.HC9A29KZQM",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"5FYNDTBJGFNQ8E2F",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"A429C66SYZ",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
            '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW1-ets-hd-success",
                        "locationType":"AWS Region",
                        "location":"US West (N. California)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"5GHU6594JCWR72VZ"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "5GHU6594JCWR72VZ.JRTCKXETXF":{
                            "priceDimensions":{
                                "5GHU6594JCWR72VZ.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (N California) $0.034 per minute for HD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"5GHU6594JCWR72VZ.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0340000000"
                                    }
                                }
                            },
                            "sku":"5GHU6594JCWR72VZ",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW2-ets-sd-error",
                        "locationType":"AWS Region",
                        "location":"US West (Oregon)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"5SRUP8SR837DJUPV"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "5SRUP8SR837DJUPV.JRTCKXETXF":{
                            "priceDimensions":{
                                "5SRUP8SR837DJUPV.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (Oregon) $0.000 for SD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"5SRUP8SR837DJUPV.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"5SRUP8SR837DJUPV",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS2-ets-audio-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Sydney)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"69U6EQ84TGYFKAHF"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "69U6EQ84TGYFKAHF.JRTCKXETXF":{
                            "priceDimensions":{
                                "69U6EQ84TGYFKAHF.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Sydney) audio that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"69U6EQ84TGYFKAHF.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"69U6EQ84TGYFKAHF",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW1-ets-sd-error",
                        "locationType":"AWS Region",
                        "location":"US West (N. California)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"6DAYWSKZJNMDZ2G5"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "6DAYWSKZJNMDZ2G5.JRTCKXETXF":{
                            "priceDimensions":{
                                "6DAYWSKZJNMDZ2G5.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (N California) $0.000 for SD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"6DAYWSKZJNMDZ2G5.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"6DAYWSKZJNMDZ2G5",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW1-ets-sd-success",
                        "locationType":"AWS Region",
                        "location":"US West (N. California)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"6KNNYA363QH2R346"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "6KNNYA363QH2R346.JRTCKXETXF":{
                            "priceDimensions":{
                                "6KNNYA363QH2R346.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (N California) $0.017 per minute for SD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"6KNNYA363QH2R346.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0170000000"
                                    }
                                }
                            },
                            "sku":"6KNNYA363QH2R346",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW1-ets-audio-success",
                        "locationType":"AWS Region",
                        "location":"US West (N. California)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"6QXAV5C23X3N6D84"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "6QXAV5C23X3N6D84.JRTCKXETXF":{
                            "priceDimensions":{
                                "6QXAV5C23X3N6D84.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (N California) $0.00522 per minute for audio-only transcoding.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"6QXAV5C23X3N6D84.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0052200000"
                                    }
                                }
                            },
                            "sku":"6QXAV5C23X3N6D84",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW2-ets-sd-success",
                        "locationType":"AWS Region",
                        "location":"US West (Oregon)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"7H54P7A8XSKKDVGF"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "7H54P7A8XSKKDVGF.JRTCKXETXF":{
                            "priceDimensions":{
                                "7H54P7A8XSKKDVGF.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (Oregon) $0.015 per minute for SD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"7H54P7A8XSKKDVGF.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0150000000"
                                    }
                                }
                            },
                            "sku":"7H54P7A8XSKKDVGF",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS3-ets-sd-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Mumbai)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"82E72WZ2BXYNPX7X"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "82E72WZ2BXYNPX7X.JRTCKXETXF":{
                            "priceDimensions":{
                                "82E72WZ2BXYNPX7X.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Mumbai) $0.000 for SD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"82E72WZ2BXYNPX7X.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"82E72WZ2BXYNPX7X",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS3-ets-hd-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Mumbai)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"8MA3Q7YGNXUC9NF8"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "8MA3Q7YGNXUC9NF8.JRTCKXETXF":{
                            "priceDimensions":{
                                "8MA3Q7YGNXUC9NF8.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Mumbai) $0.000 for HD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"8MA3Q7YGNXUC9NF8.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"8MA3Q7YGNXUC9NF8",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW1-ets-audio-error",
                        "locationType":"AWS Region",
                        "location":"US West (N. California)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"94N4V6GS7B8PS66B"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "94N4V6GS7B8PS66B.JRTCKXETXF":{
                            "priceDimensions":{
                                "94N4V6GS7B8PS66B.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (N California) audio that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"94N4V6GS7B8PS66B.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"94N4V6GS7B8PS66B",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS2-ets-audio-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Sydney)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"A2DWF9B3GF7UCPPR"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "A2DWF9B3GF7UCPPR.JRTCKXETXF":{
                            "priceDimensions":{
                                "A2DWF9B3GF7UCPPR.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Sydney) $0.00522 per minute for audio-only transcoding.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"A2DWF9B3GF7UCPPR.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0052200000"
                                    }
                                }
                            },
                            "sku":"A2DWF9B3GF7UCPPR",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"ets-sd-success",
                        "locationType":"AWS Region",
                        "location":"US East (N. Virginia)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"A4R97WC2EFV3B72Q"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "A4R97WC2EFV3B72Q.JRTCKXETXF":{
                            "priceDimensions":{
                                "A4R97WC2EFV3B72Q.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US East (N. Virginia) $0.015 per minute for SD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"A4R97WC2EFV3B72Q.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0150000000"
                                    }
                                }
                            },
                            "sku":"A4R97WC2EFV3B72Q",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"EU-ets-hd-success",
                        "locationType":"AWS Region",
                        "location":"EU (Ireland)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"ACM5HUHV5MTBSQ32"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "ACM5HUHV5MTBSQ32.JRTCKXETXF":{
                            "priceDimensions":{
                                "ACM5HUHV5MTBSQ32.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"EU (Ireland) $0.034 per minute for HD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"ACM5HUHV5MTBSQ32.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0340000000"
                                    }
                                }
                            },
                            "sku":"ACM5HUHV5MTBSQ32",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"EU-ets-hd-error",
                        "locationType":"AWS Region",
                        "location":"EU (Ireland)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"B694MF7JAZJDUSEW"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "B694MF7JAZJDUSEW.JRTCKXETXF":{
                            "priceDimensions":{
                                "B694MF7JAZJDUSEW.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"EU (Ireland) $0.000 for HD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"B694MF7JAZJDUSEW.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"B694MF7JAZJDUSEW",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW2-ets-hd-error",
                        "locationType":"AWS Region",
                        "location":"US West (Oregon)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"BE6KWNC3HZJUS2VS"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "BE6KWNC3HZJUS2VS.JRTCKXETXF":{
                            "priceDimensions":{
                                "BE6KWNC3HZJUS2VS.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (Oregon) $0.000 for HD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"BE6KWNC3HZJUS2VS.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"BE6KWNC3HZJUS2VS",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS3-ets-sd-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Mumbai)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"C2JR2Y4SZXFYKW7B"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "C2JR2Y4SZXFYKW7B.JRTCKXETXF":{
                            "priceDimensions":{
                                "C2JR2Y4SZXFYKW7B.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Mumbai) $0.015 per minute for SD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"C2JR2Y4SZXFYKW7B.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0150000000"
                                    }
                                }
                            },
                            "sku":"C2JR2Y4SZXFYKW7B",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"ets-hd-success",
                        "locationType":"AWS Region",
                        "location":"US East (N. Virginia)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"CBGFG8NH4X7QF9RZ"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "CBGFG8NH4X7QF9RZ.JRTCKXETXF":{
                            "priceDimensions":{
                                "CBGFG8NH4X7QF9RZ.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US East (N. Virginia) $0.030 per minute for HD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"CBGFG8NH4X7QF9RZ.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0300000000"
                                    }
                                }
                            },
                            "sku":"CBGFG8NH4X7QF9RZ",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS2-ets-sd-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Sydney)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"E8CZEMMMZ6VCQPSK"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "E8CZEMMMZ6VCQPSK.JRTCKXETXF":{
                            "priceDimensions":{
                                "E8CZEMMMZ6VCQPSK.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Sydney) $0.000 for SD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"E8CZEMMMZ6VCQPSK.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"E8CZEMMMZ6VCQPSK",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS1-ets-hd-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Singapore)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"EGN95ZAVRCNEWGGC"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "EGN95ZAVRCNEWGGC.JRTCKXETXF":{
                            "priceDimensions":{
                                "EGN95ZAVRCNEWGGC.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Singapore) $0.000 for HD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"EGN95ZAVRCNEWGGC.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"EGN95ZAVRCNEWGGC",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW2-ets-audio-error",
                        "locationType":"AWS Region",
                        "location":"US West (Oregon)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"F2NUSMMZ4QY68JVJ"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "F2NUSMMZ4QY68JVJ.JRTCKXETXF":{
                            "priceDimensions":{
                                "F2NUSMMZ4QY68JVJ.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (Oregon) audio that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"F2NUSMMZ4QY68JVJ.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"F2NUSMMZ4QY68JVJ",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"ets-audio-error",
                        "locationType":"AWS Region",
                        "location":"US East (N. Virginia)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"F2XQ94QWHWJ9MQTC"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "F2XQ94QWHWJ9MQTC.JRTCKXETXF":{
                            "priceDimensions":{
                                "F2XQ94QWHWJ9MQTC.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US East (N. Virginia) audio that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"F2XQ94QWHWJ9MQTC.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"F2XQ94QWHWJ9MQTC",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"ets-audio-success",
                        "locationType":"AWS Region",
                        "location":"US East (N. Virginia)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"G8X9SEMT6JWR5F5Z"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "G8X9SEMT6JWR5F5Z.JRTCKXETXF":{
                            "priceDimensions":{
                                "G8X9SEMT6JWR5F5Z.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US East (N. Virginia) $0.0045 per minute for audio-only transcoding.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"G8X9SEMT6JWR5F5Z.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0045000000"
                                    }
                                }
                            },
                            "sku":"G8X9SEMT6JWR5F5Z",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS1-ets-sd-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Singapore)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"J3XRKMYKBKWWR34R"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "J3XRKMYKBKWWR34R.JRTCKXETXF":{
                            "priceDimensions":{
                                "J3XRKMYKBKWWR34R.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Singapore) $0.000 for SD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"J3XRKMYKBKWWR34R.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"J3XRKMYKBKWWR34R",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS2-ets-hd-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Sydney)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"M285SQPS49XR3GGV"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "M285SQPS49XR3GGV.JRTCKXETXF":{
                            "priceDimensions":{
                                "M285SQPS49XR3GGV.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Sydney) $0.000 for HD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"M285SQPS49XR3GGV.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"M285SQPS49XR3GGV",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"Global-ets-hd-success",
                        "locationType":"AWS Region",
                        "location":"Any",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"M4P5XCFENNGRF7ZG"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "M4P5XCFENNGRF7ZG.A429C66SYZ":{
                            "priceDimensions":{
                                "M4P5XCFENNGRF7ZG.A429C66SYZ.DGNP37V5JW":{
                                    "unit":"minutes",
                                    "endRange":"10",
                                    "description":"Global Free Tier for HD",
                                    "appliesTo":[
                                        "CBGFG8NH4X7QF9RZ",
                                        "5GHU6594JCWR72VZ",
                                        "VVXJCQFFCNHDQSR4",
                                        "MQZ2STE75H44RXFY",
                                        "VJTHP8HDT5UYN9CA",
                                        "YF8PPTSR7UM7CZG9",
                                        "ACM5HUHV5MTBSQ32",
                                        "U5KDAK4CQ2VWEG49"
                                    ],
                                    "rateCode":"M4P5XCFENNGRF7ZG.A429C66SYZ.DGNP37V5JW",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"M4P5XCFENNGRF7ZG",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"A429C66SYZ",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS1-ets-hd-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Singapore)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"MQZ2STE75H44RXFY"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "MQZ2STE75H44RXFY.JRTCKXETXF":{
                            "priceDimensions":{
                                "MQZ2STE75H44RXFY.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Singapore) $0.034 per minute for HD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"MQZ2STE75H44RXFY.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0340000000"
                                    }
                                }
                            },
                            "sku":"MQZ2STE75H44RXFY",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS1-ets-sd-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Singapore)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"PBJ28CAUYT3C3APY"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "PBJ28CAUYT3C3APY.JRTCKXETXF":{
                            "priceDimensions":{
                                "PBJ28CAUYT3C3APY.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Singapore) $0.017 per minute for SD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"PBJ28CAUYT3C3APY.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0170000000"
                                    }
                                }
                            },
                            "sku":"PBJ28CAUYT3C3APY",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"EU-ets-sd-error",
                        "locationType":"AWS Region",
                        "location":"EU (Ireland)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"Q3TNTH98MAC9Q9CV"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "Q3TNTH98MAC9Q9CV.JRTCKXETXF":{
                            "priceDimensions":{
                                "Q3TNTH98MAC9Q9CV.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"EU (Ireland) $0.000 for SD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"Q3TNTH98MAC9Q9CV.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"Q3TNTH98MAC9Q9CV",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APN1-ets-audio-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Tokyo)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"TBRWHTQQ68FUJ23W"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "TBRWHTQQ68FUJ23W.JRTCKXETXF":{
                            "priceDimensions":{
                                "TBRWHTQQ68FUJ23W.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Tokyo) $0.00522 per minute for audio-only transcoding.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"TBRWHTQQ68FUJ23W.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0052200000"
                                    }
                                }
                            },
                            "sku":"TBRWHTQQ68FUJ23W",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"EU-ets-audio-error",
                        "locationType":"AWS Region",
                        "location":"EU (Ireland)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"THWWGST7GXXEMM2P"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "THWWGST7GXXEMM2P.JRTCKXETXF":{
                            "priceDimensions":{
                                "THWWGST7GXXEMM2P.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"EU (Ireland) audio that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"THWWGST7GXXEMM2P.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"THWWGST7GXXEMM2P",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS2-ets-hd-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Sydney)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"U5KDAK4CQ2VWEG49"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "U5KDAK4CQ2VWEG49.JRTCKXETXF":{
                            "priceDimensions":{
                                "U5KDAK4CQ2VWEG49.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Sydney) $0.034 per minute for HD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"U5KDAK4CQ2VWEG49.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0340000000"
                                    }
                                }
                            },
                            "sku":"U5KDAK4CQ2VWEG49",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APN1-ets-sd-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Tokyo)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"V35CETBNGB4SQT2W"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "V35CETBNGB4SQT2W.JRTCKXETXF":{
                            "priceDimensions":{
                                "V35CETBNGB4SQT2W.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Tokyo) $0.017 per minute for SD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"V35CETBNGB4SQT2W.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0170000000"
                                    }
                                }
                            },
                            "sku":"V35CETBNGB4SQT2W",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS3-ets-hd-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Mumbai)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"VJTHP8HDT5UYN9CA"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "VJTHP8HDT5UYN9CA.JRTCKXETXF":{
                            "priceDimensions":{
                                "VJTHP8HDT5UYN9CA.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Mumbai) $0.030 per minute for HD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"VJTHP8HDT5UYN9CA.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0300000000"
                                    }
                                }
                            },
                            "sku":"VJTHP8HDT5UYN9CA",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APN1-ets-sd-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Tokyo)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"VRX2E85QV2N79R6N"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "VRX2E85QV2N79R6N.JRTCKXETXF":{
                            "priceDimensions":{
                                "VRX2E85QV2N79R6N.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Tokyo) $0.000 for SD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"VRX2E85QV2N79R6N.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"VRX2E85QV2N79R6N",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"APN1-ets-hd-success",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Tokyo)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"VVXJCQFFCNHDQSR4"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "VVXJCQFFCNHDQSR4.JRTCKXETXF":{
                            "priceDimensions":{
                                "VVXJCQFFCNHDQSR4.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Tokyo) $0.034 per minute for HD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"VVXJCQFFCNHDQSR4.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0340000000"
                                    }
                                }
                            },
                            "sku":"VVXJCQFFCNHDQSR4",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Standard Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"EU-ets-sd-success",
                        "locationType":"AWS Region",
                        "location":"EU (Ireland)",
                        "videoResolution":"Less than 720p",
                        "operation":""
                    },
                    "sku":"VW6FJWRWVMBC5TBY"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "VW6FJWRWVMBC5TBY.JRTCKXETXF":{
                            "priceDimensions":{
                                "VW6FJWRWVMBC5TBY.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"EU (Ireland) $0.017 per minute for SD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"VW6FJWRWVMBC5TBY.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0170000000"
                                    }
                                }
                            },
                            "sku":"VW6FJWRWVMBC5TBY",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW1-ets-hd-error",
                        "locationType":"AWS Region",
                        "location":"US West (N. California)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"W2HGVVFS6R8Y863H"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "W2HGVVFS6R8Y863H.JRTCKXETXF":{
                            "priceDimensions":{
                                "W2HGVVFS6R8Y863H.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (N California) $0.000 for HD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"W2HGVVFS6R8Y863H.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"W2HGVVFS6R8Y863H",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"global-ets-audio-success",
                        "locationType":"AWS Region",
                        "location":"Any",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"WDSV22AHS3FNCC2T"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "WDSV22AHS3FNCC2T.A429C66SYZ":{
                            "priceDimensions":{
                                "WDSV22AHS3FNCC2T.A429C66SYZ.HC9A29KZQM":{
                                    "unit":"minutes",
                                    "endRange":"20",
                                    "description":"Global Free Tier for audio",
                                    "appliesTo":[
                                        "6QXAV5C23X3N6D84",
                                        "A2DWF9B3GF7UCPPR",
                                        "2PS5CWVUXYHZC42Q",
                                        "G8X9SEMT6JWR5F5Z",
                                        "XJ3HVNKEQ8QHZYQQ",
                                        "TBRWHTQQ68FUJ23W",
                                        "2N4MPTUXKPSSU7VJ",
                                        "2M7EQWCEEZSJ69U7"
                                    ],
                                    "rateCode":"WDSV22AHS3FNCC2T.A429C66SYZ.HC9A29KZQM",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"WDSV22AHS3FNCC2T",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"A429C66SYZ",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS1-ets-audio-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Singapore)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"X3ZQVUYKP78J9PFT"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "X3ZQVUYKP78J9PFT.JRTCKXETXF":{
                            "priceDimensions":{
                                "X3ZQVUYKP78J9PFT.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Singapore) audio that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"X3ZQVUYKP78J9PFT.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"X3ZQVUYKP78J9PFT",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"EU-ets-audio-success",
                        "locationType":"AWS Region",
                        "location":"EU (Ireland)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"XJ3HVNKEQ8QHZYQQ"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "XJ3HVNKEQ8QHZYQQ.JRTCKXETXF":{
                            "priceDimensions":{
                                "XJ3HVNKEQ8QHZYQQ.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"EU (Ireland) $0.00522 per minute for audio-only transcoding.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"XJ3HVNKEQ8QHZYQQ.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0052200000"
                                    }
                                }
                            },
                            "sku":"XJ3HVNKEQ8QHZYQQ",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"Audio",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APS3-ets-audio-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Mumbai)",
                        "videoResolution":"Audio only",
                        "operation":""
                    },
                    "sku":"XJW24V4SCXQUGVP8"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "XJW24V4SCXQUGVP8.JRTCKXETXF":{
                            "priceDimensions":{
                                "XJW24V4SCXQUGVP8.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Mumbai) audio that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"XJW24V4SCXQUGVP8.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"XJW24V4SCXQUGVP8",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Success",
                        "servicecode":"AmazonETS",
                        "usagetype":"USW2-ets-hd-success",
                        "locationType":"AWS Region",
                        "location":"US West (Oregon)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"YF8PPTSR7UM7CZG9"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "YF8PPTSR7UM7CZG9.JRTCKXETXF":{
                            "priceDimensions":{
                                "YF8PPTSR7UM7CZG9.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"US West (Oregon) $0.030 per minute for HD content.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"YF8PPTSR7UM7CZG9.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0300000000"
                                    }
                                }
                            },
                            "sku":"YF8PPTSR7UM7CZG9",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }',
           '{
                "product":{
                    "productFamily":"High Definition",
                    "attributes":{
                        "transcodingResult":"Error",
                        "servicecode":"AmazonETS",
                        "usagetype":"APN1-ets-hd-error",
                        "locationType":"AWS Region",
                        "location":"Asia Pacific (Tokyo)",
                        "videoResolution":"720p and above",
                        "operation":""
                    },
                    "sku":"Z49M4D8ES2PX5C3P"
                },
                "serviceCode":"AmazonETS",
                "terms":{
                    "OnDemand":{
                        "Z49M4D8ES2PX5C3P.JRTCKXETXF":{
                            "priceDimensions":{
                                "Z49M4D8ES2PX5C3P.JRTCKXETXF.6YS6EN2CT7":{
                                    "unit":"minutes",
                                    "endRange":"Inf",
                                    "description":"Asia Pacific (Tokyo) $0.000 for HD content that failed to transcode.",
                                    "appliesTo":[
                                    ],
                                    "rateCode":"Z49M4D8ES2PX5C3P.JRTCKXETXF.6YS6EN2CT7",
                                    "beginRange":"0",
                                    "pricePerUnit":{
                                        "USD":"0.0000000000"
                                    }
                                }
                            },
                            "sku":"Z49M4D8ES2PX5C3P",
                            "effectiveDate":"2016-09-01T00:00:00Z",
                            "offerTermCode":"JRTCKXETXF",
                            "termAttributes":{
                            }
                        }
                    }
                },
                "version":"20170419202053",
                "publicationDate":"2017-04-19T20:20:53Z"
            }'
        ],
        '@metadata' => [
            'statusCode' => 200,
            'effectiveUri' => 'https://api.pricing.us-east-1.amazonaws.com',
        ]
    ]
];
