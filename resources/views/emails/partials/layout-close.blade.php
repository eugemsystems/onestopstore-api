{{-- Closing partial for shared email layout --}}
    </div>{{-- /.email-body --}}

    {{-- ── Footer ── --}}
    <div class="email-footer">
        <p class="footer-brand">{{ config('app.name', 'Raines Africa') }}</p>
        @if(!empty($isInteractive) && $isInteractive)
            <p>Questions? Email us at <a href="mailto:admin@raines.africa">admin@raines.africa</a></p>
        @else
            <p>This is an automated email. Please do not reply directly to this message.</p>
        @endif
        <p style="margin-top:12px;">
            <a href="{{ config('app.frontend_url', 'https://raines.africa') }}">raines.africa</a>
            &nbsp;|&nbsp;
            <a href="mailto:admin@raines.africa">Contact Support</a>
        </p>
        <p style="margin-top:12px;">&copy; {{ date('Y') }} Raines Africa. All rights reserved.</p>
    </div>

</div>{{-- /.email-wrap --}}
</body>
</html>
