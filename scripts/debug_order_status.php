<?php
$logs = DB::table('inventory_receiving_logs')->whereNotNull('order_number')->limit(3)->pluck('order_number');
$orders = DB::table('orders')->whereIn('order_number', $logs)->leftJoin('order_statuses','orders.order_status_id','=','order_statuses.id')->select('orders.order_number','order_statuses.name as status')->get();
echo json_encode(['log_order_numbers' => $logs, 'orders_found' => $orders], JSON_PRETTY_PRINT);
