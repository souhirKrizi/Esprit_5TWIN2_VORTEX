
<div class="auth-illustration relative w-full rounded-2xl bg-surface-container-low p-space-lg overflow-hidden shadow-sm">
<!-- Halos diffusés d'ondes thermiques en fond -->
<div class="absolute -top-12 -right-12 w-48 h-48 rounded-full bg-secondary-container/20 blur-2xl pointer-events-none"></div>
<div class="absolute bottom-4 left-1/4 w-36 h-36 rounded-full bg-surface-variant/70 blur-xl pointer-events-none"></div>
<div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-space-lg">
<!-- Carte Vectorielle Épurée de la Tunisie -->
<div class="w-full max-w-[280px] h-[270px] relative flex items-center justify-center shrink-0">
<svg class="w-full h-full drop-shadow-sm select-none" fill="none" viewbox="0 0 240 300" xmlns="http://www.w3.org/2000/svg">
<defs>
<lineargradient gradientunits="userSpaceOnUse" id="tunisiaGradient" x1="50" x2="190" y1="20" y2="280">
<stop offset="0%" stop-color="#FFFFFF"></stop>
<stop offset="60%" stop-color="#E5EEFF"></stop>
<stop offset="100%" stop-color="#FFDBCC"></stop>
</lineargradient>
<lineargradient id="heatAura" x1="0" x2="1" y1="0" y2="1">
<stop offset="0%" stop-color="#FF8343" stop-opacity="0.45"></stop>
<stop offset="100%" stop-color="#A14000" stop-opacity="0.05"></stop>
</lineargradient>
<radialgradient cx="50%" cy="50%" id="sunPulse" r="50%">
<stop offset="0%" stop-color="#FF8343" stop-opacity="0.9"></stop>
<stop offset="70%" stop-color="#FF8343" stop-opacity="0.3"></stop>
<stop offset="100%" stop-color="#FF8343" stop-opacity="0"></stop>
</radialgradient>
</defs>
<!-- Ondes de chaleur atmosphériques stylisées -->
<circle class="animate-pulse" cx="160" cy="80" fill="url(#sunPulse)" r="54"></circle>
<path d="M 175 40 Q 185 55 175 70 T 175 100" opacity="0.6" stroke="#FF8343" stroke-linecap="round" stroke-width="2"></path>
<path d="M 190 50 Q 200 65 190 80 T 190 110" opacity="0.4" stroke="#FF8343" stroke-linecap="round" stroke-width="1.5"></path>
<!-- Silhouette Stylisée Géométrique du littoral & frontières de la Tunisie -->
<path d="M 75 40 
                         C 90 28, 125 22, 138 35 
                         C 150 45, 142 62, 160 70 
                         C 175 78, 172 105, 158 115 
                         C 145 125, 155 145, 150 165 
                         C 142 190, 162 205, 145 235 
                         C 135 250, 120 285, 110 282 
                         C 100 278, 85 245, 78 220 
                         C 70 195, 62 170, 68 145 
                         C 72 120, 60 90, 66 65 Z" fill="url(#tunisiaGradient)"></path>
<!-- Isolignes thermiques intérieures subtiles -->
<path d="M 85 85 C 105 80, 130 95, 150 90" stroke="#D9E3F5" stroke-dasharray="3 3" stroke-width="1.5"></path>
<path d="M 80 135 C 100 130, 120 145, 140 140" opacity="0.5" stroke="#FF8343" stroke-width="1.5"></path>
<path d="M 82 185 C 105 180, 125 195, 140 190" opacity="0.6" stroke="#A14000" stroke-width="1.5"></path>
<!-- Repères Gouvernorats Clés -->
<!-- Bizerte -->
<circle cx="118" cy="38" fill="#111D35" r="3.5"></circle>
<text class="font-label-sm text-[8px] font-bold" fill="#111D35" x="124" y="39">Bizerte</text>
<!-- Tunis -->
<circle cx="132" cy="56" fill="#A14000" r="4"></circle>
<circle cx="132" cy="56" opacity="0.75" r="8" stroke="#FF8343" stroke-width="1.5"></circle>
<text class="font-label-sm text-[9px] font-bold" fill="#111D35" x="140" y="58">Tunis</text>
<!-- Kairouan (Centre - Alerte Chaleur) -->
<circle cx="116" cy="112" fill="#BA1A1A" r="4.5"></circle>
<circle class="animate-ping" cx="116" cy="112" opacity="0.4" r="9" stroke="#BA1A1A" stroke-width="1.5" style="animation-duration: 2.5s;"></circle>
<text class="font-label-sm text-[9px] font-bold" fill="#BA1A1A" x="124" y="114">Kairouan</text>
<!-- Sfax -->
<circle cx="140" cy="148" fill="#111D35" r="3.5"></circle>
<text class="font-label-sm text-[8px] font-bold" fill="#111D35" x="148" y="150">Sfax</text>
<!-- Tozeur -->
<circle cx="82" cy="178" fill="#FF8343" r="4"></circle>
<text class="font-label-sm text-[8px] font-bold" fill="#A14000" x="44" y="180">Tozeur</text>
<!-- Tataouine -->
<circle cx="120" cy="235" fill="#111D35" r="3.5"></circle>
<text class="font-label-sm text-[8px] font-bold" fill="#111D35" x="128" y="237">Tataouine</text>
</svg>
</div>
<!-- Balises de données et niveau d'alerte en direct -->
<div class="flex flex-col gap-space-sm w-full">
<!-- Indicateur Canicule Actuel -->
<div class="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex items-center justify-between">
<div class="flex items-center gap-space-sm">
<div class="w-10 h-10 rounded-lg bg-secondary-container/15 flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">thermostat</span>
</div>
<div class="flex flex-col">
<span class="font-label-sm text-label-sm text-on-surface-variant uppercase font-bold">Pic thermique national</span>
<span class="font-headline-md text-headline-md text-primary-container leading-none">38.4°C</span>
</div>
</div>
<div class="px-space-sm py-space-xs rounded-full bg-secondary-container/20 text-on-secondary-container font-label-sm text-label-sm font-bold">
                  Vigilance Orange
                </div>
</div>
<!-- Statut Réseau Civique -->
<div class="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex items-center gap-space-sm">
<div class="w-10 h-10 rounded-lg bg-surface-container flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-primary-container">shield_with_heart</span>
</div>
<div class="flex flex-col min-w-0">
<span class="font-label-md text-label-md text-primary-container font-bold truncate">Protection des populations sensibles</span>
<span class="font-body-sm text-body-sm text-on-surface-variant truncate">Seuils déclenchés selon les normes INM &amp; OMS</span>
</div>
</div>
<!-- Flux d'alerte instantanée -->
<div class="flex items-center gap-space-xs text-on-surface-variant px-space-xs">
<span class="w-2 h-2 rounded-full bg-secondary-container animate-pulse"></span>
<span class="font-label-sm text-label-sm">Données synchronisées toutes les 15 minutes</span>
</div>
</div>
</div>
</div>
