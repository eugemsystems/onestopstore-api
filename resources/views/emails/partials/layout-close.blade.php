{{-- Closing partial for shared email layout --}}
    </div>{{-- /.email-body --}}

    {{-- ── Footer ── --}}
    <div class="email-footer">
        <p class="footer-brand">{{ config('app.name', 'One Stop Store') }}</p>
        @if(!empty($isInteractive) && $isInteractive)
            <p>Questions? Email us at <a href="mailto:admin@onestopstore.co.zw">admin@onestopstore.co.zw</a></p>
        @else
            <p>This is an automated email. Please do not reply directly to this message.</p>
        @endif
        <p style="margin-top:12px;">
            <a href="{{ config('app.frontend_url', 'https://onestopstore.co.zw') }}">onestopstore.co.zw</a>
            &nbsp;|&nbsp;
            <a href="mailto:admin@onestopstore.co.zw">Contact Support</a>
        </p>
        <p style="margin-top:12px;">&copy; {{ date('Y') }} One Stop Store. All rights reserved.</p>
    </div>

</div>{{-- /.email-wrap --}}
</body>
</html>
