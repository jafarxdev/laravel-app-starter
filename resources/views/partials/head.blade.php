<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ isset($title) ? $title.' · '.$appSettings['application_name'] : $appSettings['application_name'] }}</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
