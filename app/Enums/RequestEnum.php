<?php

namespace App\Enums;

enum RequestEnum:string {
  const PENDING = 'pending';
  const COMPLETED = 'completed';
  const REJECTED = 'rejected';
  const APPROVED = 'approved';
}
