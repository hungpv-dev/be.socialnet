<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Khai báo các thuộc tính trực tiếp
    public $name;
    public $subject;
    public $causer;
    public $properties;
    public $log;

    // Constructor nhận các tham số và gán trực tiếp vào các thuộc tính
    public function __construct($name, $causer, $subject, $properties, $log)
    {
        $this->name = $name;
        $this->subject = $subject;
        $this->causer = $causer;
        $this->properties = $properties;
        $this->log = $log;
    }

    // Phương thức handle để thực thi hành động
    public function handle()
    {
        activity($this->name)
            ->causedBy($this->causer)
            ->performedOn($this->subject)
            ->withProperties($this->properties)
            ->log($this->log);
    }
}
