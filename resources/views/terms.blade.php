<x-guest-layout>
    <article class="legal-document">
        <nav aria-label="Account navigation">
            <a href="{{ route('login') }}">{{ __('Log in') }}</a>
            <a href="{{ route('register') }}">Create an account</a>
            <a href="{{ route('policy.show') }}">{{ __('Privacy notice') }}</a>
            <a href="{{ route('terms.show') }}">{{ __('Terms of use') }}</a>
        </nav>
        {!! $terms !!}
    </article>
</x-guest-layout>
