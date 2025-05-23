<?php 
    $query_array=array("headline"=>__("All Partitions"),
                       "sql"=>"SELECT * FROM `partition`, system, graphs_disk WHERE system_uuid = partition_uuid AND system_uuid = disk_uuid AND partition_timestamp = system_timestamp AND system_timestamp = disk_timestamp AND partition_caption = disk_letter",
                       "sort"=>"system_name, partition_caption",
                       "dir"=>"ASC",
                       "get"=>array("file"=>"system.php",
                                    "title"=>__("Go to System"),
                                    "var"=>array("pc"=>"%system_uuid",
                                                 "view"=>"summary",
                                                ),
                                   ),
                       "fields"=>array("10"=>array("name"=>"system_uuid",
                                                   "head"=>__("UUID"),
                                                   "show"=>"n",
                                                  ),
                                       "20"=>array("name"=>"system_name",
                                                   "head"=>__("Hostname"),
                                                   "show"=>"y",
                                                   "link"=>"y",
                                                  ),
									   "21"=>array("name"=>"",
												   "head"=>__("4C"),
												   "show"=>"y",
												   "link"=>"n",
												  ),
                                       "30"=>array("name"=>"partition_caption",
                                                   "head"=>__("Drive Letter"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                       "40"=>array("name"=>"partition_volume_name",
                                                   "head"=>__("Volume Name"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                       "60"=>array("name"=>"partition_size",
                                                   "head"=>__("Partition Size"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                       "70"=>array("name"=>"partition_free_space",
                                                   "head"=>__("Free Space"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                       "75"=>array("name"=>"partition_used_space",
                                                   "head"=>__("Belegt"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                       "80"=>array("name"=>"disk_percent",
                                                   "head"=>__("Percent Used"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                       "90"=>array("name"=>"partition_file_system",
                                                   "head"=>__("File System"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                       "92"=>array("name"=>"partition_boot_partition",
                                                   "head"=>__("System"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                       "93"=>array("name"=>"partition_type",
                                                   "head"=>__("GPT/MBR"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                       "94"=>array("name"=>"partition_bitlocker",
                                                   "head"=>__("Bitlocker"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                       "96"=>array("name"=>"partition_device_id",
                                                   "head"=>__("device id"),
                                                   "show"=>"y",
                                                   "link"=>"n",
                                                  ),
                                      ),
                         )
?>