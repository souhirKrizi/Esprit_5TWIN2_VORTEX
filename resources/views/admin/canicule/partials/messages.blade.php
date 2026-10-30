@if(session('success'))<p class="ha-notice ha-notice-success" role="status">{{ session('success') }}</p>@endif
@if(session('error'))<p class="ha-notice ha-notice-error" role="alert">{{ session('error') }}</p>@endif
@if($errors->any())<p class="ha-notice ha-notice-error" role="alert">Vérifiez les champs signalés dans le formulaire.</p>@endif