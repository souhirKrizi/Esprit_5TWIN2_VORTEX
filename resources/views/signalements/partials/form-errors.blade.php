@if($errors->any())
    <div class="ha-notice ha-notice-error" role="alert">
        <strong>Vérifiez les informations saisies.</strong>
        <ul class="ha-error-list">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
