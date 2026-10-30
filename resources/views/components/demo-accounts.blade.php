@if (app()->environment(['local', 'testing']))
    <section class="auth-demo" aria-labelledby="demo-accounts-title">
        <div class="auth-demo-heading">
            <p class="auth-form-kicker">EXPLORE TUKLAS</p>
            <h3 id="demo-accounts-title">Try a demo account</h3>
            <p>Preview one account for each role. Choose a role to open its dashboard.</p>
        </div>

        <div class="auth-demo-list">
            @foreach ([App\Enums\Role::Youth, App\Enums\Role::Trainer, App\Enums\Role::SuperAdmin] as $role)
                <form method="POST" action="{{ route('demo.login', $role->value) }}" class="auth-demo-card">
                    @csrf
                    <div>
                        <strong>{{ $role->label() }}</strong>
                        <span>demo-{{ $role->value }}@tuklas.test</span>
                    </div>
                    <button type="submit" aria-label="Open {{ $role->label() }} demo">Open demo <span aria-hidden="true">→</span></button>
                </form>
            @endforeach
        </div>
    </section>
@endif
