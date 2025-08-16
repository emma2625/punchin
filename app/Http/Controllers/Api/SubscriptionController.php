<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function  index() {
        $subscriptions = Subscription::orderBy('price', 'asc')->get();
        return SubscriptionResource::collection($subscriptions);
    }
}
