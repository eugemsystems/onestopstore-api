Dear {{ $vendorName }},

⚠️ ACCOUNT SUSPENDED ⚠️

We regret to inform you that your vendor account for {{ $storeName }} has been SUSPENDED on Raines Africa.

@if($banReason)
REASON FOR SUSPENSION:
{{ $banReason }}
@endif

WHAT THIS MEANS:
• Your store is no longer visible to customers
• All your products have been deactivated
• You cannot access your seller dashboard
• Pending orders will be handled according to our policies

WHAT YOU CAN DO:
If you believe this suspension was made in error or if you would like to appeal this decision, please contact our support team immediately:

📧 Email: admin@raines.africa
📞 Phone: +263779411028 | +260777265389

IMPORTANT: Please include your Store ID ({{ $store->id }}) in all communications regarding this matter.

We take vendor compliance seriously to maintain the integrity and quality of our marketplace. We appreciate your understanding.

Best regards,
The Raines Africa Team

---
This is an automated message from Raines Africa.
Visit us at: {{ config('app.url') }}
© {{ date('Y') }} Raines Africa. All rights reserved.

