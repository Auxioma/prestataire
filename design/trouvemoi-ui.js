(function () {
  'use strict';

  var FILES = {
    clientSettings: 'trouvemoi-client-parametres-profil.html',
    servicesSettings: 'trouvemoi-prestataire-parametres-services.html',
    quoteSettings: 'trouvemoi-prestataire-parametres-devis-paiement.html',
    revenues: 'trouvemoi-prestataire-mes-revenus.html',
    quotes: 'trouvemoi-prestataire-mes-devis.html',
    reviews: 'trouvemoi-prestataire-mes-avis.html',
    verification: 'trouvemoi-verification-profils.html',
    companyInfo: 'trouvemoi-prestataire-parametres-informations.html',
    clientConversations: 'trouvemoi-client-conversations.html',
    servicesList: 'trouvemoi-prestataire-prestations-liste.html',
    servicesDashboard: 'trouvemoi-prestataire-prestations-dashboard.html',
    agencyInfo: 'trouvemoi-agence-parametres-informations.html',
    notifications: 'trouvemoi-prestataire-notifications.html',
    dashboard: 'trouvemoi-prestataire-tableau-de-bord.html',
    requests: 'trouvemoi-prestataire-mes-demandes.html',
    projects: 'trouvemoi-prestataire-mes-chantiers.html',
    statistics: 'trouvemoi-prestataire-statistiques.html',
    messages: 'trouvemoi-prestataire-messages.html',
    zones: 'trouvemoi-prestataire-zones-intervention.html',
    servicesZones: 'trouvemoi-prestataire-parametres-services-zones.html',
    favorites: 'trouvemoi-client-mes-favoris.html',
    photos: 'trouvemoi-prestataire-parametres-photos-realisations.html',
    security: 'trouvemoi-prestataire-securite.html',
    categories: 'trouvemoi-toutes-les-categories-v2.html'
  };

  var ICONS = {
    home: '<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
    inbox: '<path d="M4 4h16v15H4z"/><path d="m4 13 4-4h8l4 4"/><path d="M9 13h6"/>',
    briefcase: '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M8 6V4h8v2M3 11h18M9 11v2h6v-2"/>',
    file: '<path d="M6 2h9l4 4v16H6z"/><path d="M14 2v5h5M9 12h6M9 16h6"/>',
    star: '<path d="m12 2 3 6 6.5 1-4.7 4.6 1.1 6.5L12 17l-5.9 3.1 1.1-6.5L2.5 9 9 8z"/>',
    wallet: '<path d="M3 6h16v14H3z"/><path d="M3 9h18v7h-5a3 3 0 1 1 0-6h5"/>',
    calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/>',
    message: '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3 1.5-5A8 8 0 1 1 21 15z"/>',
    settings: '<circle cx="12" cy="12" r="3"/><path d="M19 13.5V10.5l-2-.7-.8-1.9.9-1.9-2.1-2.1-1.9.9-1.9-.8-.7-2h-3l-.7 2-1.9.8-1.9-.9L.9 6l.9 1.9L1 9.8l-2 .7v3l2 .7.8 1.9-.9 1.9L3 20.1l1.9-.9 1.9.8.7 2h3l.7-2 1.9-.8 1.9.9 2.1-2.1-.9-1.9.8-1.9z" transform="translate(2 0) scale(.83)"/>',
    help: '<circle cx="12" cy="12" r="9"/><path d="M9.8 9a2.4 2.4 0 1 1 3.8 2c-1 .7-1.6 1.1-1.6 2.5M12 17h.01"/>',
    bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
    mail: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    chevron: '<path d="m9 6 6 6-6 6"/>',
    down: '<path d="m6 9 6 6 6-6"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    download: '<path d="M12 3v12m0 0 4-4m-4 4-4-4M4 20h16"/>',
    eye: '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"/><circle cx="12" cy="12" r="2.5"/>',
    more: '<circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/>',
    shield: '<path d="M12 2 4 5v6c0 5 3.4 8.8 8 11 4.6-2.2 8-6 8-11V5z"/><path d="m9 12 2 2 4-5"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    building: '<path d="M4 21V5l8-3v19M12 8h8v13M7 7h2M7 11h2M7 15h2M15 11h2M15 15h2M2 21h20"/>',
    wrench: '<path d="M14 7a5 5 0 0 0-6.4-4.8l3.1 3.1-3.4 3.4-3.1-3.1A5 5 0 0 0 10 12l8 8 3-3-8-8"/>',
    pin: '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="2.5"/>',
    lock: '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
    check: '<path d="m5 12 4 4L19 6"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>',
    x: '<path d="m6 6 12 12M18 6 6 18"/>',
    chart: '<path d="M4 20V10M10 20V4M16 20v-7M22 20V7"/>',
    heart: '<path d="M20.8 4.7a5.5 5.5 0 0 0-7.8 0L12 5.8l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.5a5.5 5.5 0 0 0 0-7.8z"/>',
    camera: '<path d="M4 7h4l2-3h4l2 3h4v13H4z"/><circle cx="12" cy="13" r="4"/>',
    phone: '<path d="M5 3h4l2 5-3 2c1.5 3 3 4.5 6 6l2-3 5 2v4c0 1-1 2-2 2C10 21 3 14 3 5c0-1 1-2 2-2z"/>',
    send: '<path d="m3 3 18 9-18 9 4-9zM7 12h14"/>',
    filter: '<path d="M4 5h16l-6 7v6l-4 2v-8z"/>',
    pause: '<path d="M9 5v14M15 5v14"/>',
    map: '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3zM9 3v15M15 6v15"/>',
    image: '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 15-5-5L5 20"/>',
    upload: '<path d="M12 16V4m0 0-4 4m4-4 4 4M4 17v4h16v-4"/>',
    edit: '<path d="m4 16-1 5 5-1L19 9l-4-4zM13 7l4 4"/>',
    gift: '<rect x="3" y="9" width="18" height="12"/><path d="M12 9v12M2 9h20V5H2zM12 5c-3 0-5-1-5-3 3 0 5 1 5 3zm0 0c3 0 5-1 5-3-3 0-5 1-5 3z"/>'
  };

  function icon(name, extra) {
    return '<svg class="icon ' + (extra || '') + '" viewBox="0 0 24 24" aria-hidden="true">' + (ICONS[name] || ICONS.star) + '</svg>';
  }

  function avatar(id, sizeClass) {
    return '<img class="avatar ' + (sizeClass || '') + '" src="https://i.pravatar.cc/120?img=' + (id || 12) + '" alt="">';
  }

  function brand(role) {
    return '<a class="brand" href="' + FILES.dashboard + '"><span class="brand-mark"></span><span>TrouveMoi<small>' + (role || 'Prestataires') + '</small></span></a>';
  }

  function badge(text, color) {
    return '<span class="badge ' + (color || '') + '">' + text + '</span>';
  }

  function switcher(on, color) {
    return '<span class="switch ' + (on ? 'on ' : '') + (color || '') + '" role="switch" aria-checked="' + (on ? 'true' : 'false') + '" tabindex="0"></span>';
  }

  function button(text, type, iconName, attrs) {
    return '<button class="btn ' + (type || '') + '" ' + (attrs || '') + '>' + (iconName ? icon(iconName, 'icon-sm') : '') + text + '</button>';
  }

  function providerNav(active, includeSettings) {
    var main = [
      ['dashboard', 'Tableau de bord', 'home', ''],
      ['requests', 'Mes demandes', 'inbox', '12'],
      ['quotes', 'Mes devis', 'file', '18'],
      ['projects', 'Mes chantiers', 'wrench', ''],
      ['servicesDashboard', 'Mes prestations', 'briefcase', ''],
      ['reviews', 'Mes avis', 'star', '24'],
      ['revenues', 'Mes revenus', 'wallet', ''],
      ['messages', 'Messages', 'message', '8'],
      ['statistics', 'Statistiques', 'chart', '']
    ];
    var settings = [
      ['companyInfo', 'Informations', 'user'],
      ['servicesZones', 'Services et zones', 'settings'],
      ['photos', 'Photos et réalisations', 'image'],
      ['messages', 'Communication & Contacts', 'message'],
      ['quoteSettings', 'Devis et paiement', 'wallet'],
      ['security', 'Sécurité', 'shield'],
      ['notifications', 'Notifications', 'bell'],
      ['clientSettings', 'Préférences', 'settings']
    ];
    var out = '<div class="nav-label">Navigation</div><nav class="side-nav">';
    main.forEach(function (item) {
      out += '<a class="side-link ' + (active === item[0] ? 'active' : '') + '" href="' + (FILES[item[0]] || '#') + '">' + icon(item[2]) + '<span>' + item[1] + '</span>' + (item[3] ? '<span class="count">' + item[3] + '</span>' : '') + '</a>';
    });
    out += '</nav>';
    if (includeSettings) {
      out += '<div class="nav-label">Paramètres</div><nav class="side-nav">';
      settings.forEach(function (item) {
        out += '<a class="side-link ' + (active === item[0] ? 'active' : '') + '" href="' + (FILES[item[0]] || '#') + '">' + icon(item[2]) + '<span>' + item[1] + '</span></a>';
      });
      out += '</nav>';
    } else {
      out += '<nav class="side-nav"><a class="side-link ' + (active === 'settings' ? 'active' : '') + '" href="' + FILES.servicesSettings + '">' + icon('settings') + '<span>Paramètres</span></a><a class="side-link" href="#">' + icon('help') + '<span>Besoin d’aide</span></a></nav>';
    }
    return out;
  }

  function providerSidebar(active, includeSettings, light) {
    return '<aside class="sidebar ' + (light ? 'light' : '') + '">' +
      brand('Prestataires') +
      '<section class="profile-card">' +
        avatar(12, 'avatar-lg') +
        '<h3>' + (light ? 'Sophie Martin' : (includeSettings ? 'Plomberie Express' : 'Jean Dupont')) + '</h3>' +
        '<p>' + (light ? 'Cliente depuis mai 2024' : (includeSettings ? '<span class="verified">Compte vérifié</span>' : 'Électricien')) + '</p>' +
        '<p><span class="stars">★ ★ ★ ★ ★</span> &nbsp;4,8 (142 avis)</p>' +
        (!light && !includeSettings ? '<span class="verified">✓ Compte vérifié</span>' : '') +
      '</section>' +
      providerNav(active, includeSettings) +
      (!light ? '<section class="referral">' + icon('gift', 'icon-lg') + '<strong>Parrainez un collègue</strong><p>Gagnez 50 € de crédit</p>' + button('Parrainer maintenant', '', '', 'data-toast="Invitation prête à être envoyée"') + '</section>' : '') +
    '</aside>';
  }

  function topbar(options) {
    options = options || {};
    return '<header class="topbar ' + (options.dark ? 'dark' : '') + '">' +
      '<button class="top-icon mobile-menu" aria-label="Menu">' + icon('settings') + '</button>' +
      (options.context ? '<div class="page-context">' + options.context + '</div>' : '') +
      (options.search ? '<label class="top-search"><input type="search" placeholder="Rechercher un prestataire, une activité...">' + icon('search') + '</label>' : '<div style="margin-right:auto"></div>') +
      (options.dark ? '<nav class="top-actions"><a href="' + FILES.categories + '">Catégories⌄</a><a href="#">Bons plans</a><a href="' + FILES.favorites + '">' + icon('heart') + ' Mes favoris</a></nav>' : '') +
      '<div class="top-actions">' +
        '<button class="top-icon">' + icon('bell') + '<span class="notification-dot">2</span></button>' +
        '<button class="top-icon">' + icon('mail') + '</button>' +
        '<div class="user-chip">' + avatar(options.client ? 47 : 12, 'avatar-sm') + '<span><strong>' + (options.client ? 'Sophie Martin' : (options.company ? 'Plomberie Express' : 'Jean Dupont')) + '</strong><small>' + (options.client ? 'Cliente' : 'Prestataire') + '</small></span>' + icon('down', 'icon-sm') + '</div>' +
      '</div>' +
    '</header>';
  }

  function providerShell(content, options) {
    options = options || {};
    return '<div class="provider-shell">' +
      providerSidebar(options.active || '', !!options.includeSettings, false) +
      topbar({ dark: !!options.darkTop, search: !!options.darkTop, context: options.context || (!options.darkTop ? 'Paramètres' : ''), company: !!options.includeSettings }) +
      '<main class="workspace"><div class="page ' + (options.wide ? 'wide' : '') + '">' + content + '</div></main>' +
    '</div>';
  }

  function clientNav(active) {
    var items = [
      ['dashboard', 'Tableau de bord', 'home'],
      ['requests', 'Mes demandes', 'inbox'],
      ['quotes', 'Mes devis', 'file'],
      ['clientConversations', 'Mes conversations', 'message'],
      ['favorites', 'Mes favoris', 'heart'],
      ['reviews', 'Mes avis', 'star'],
      ['revenues', 'Paiements et factures', 'wallet'],
      ['clientSettings', 'Paramètres', 'settings']
    ];
    return '<div class="nav-label">Espace client</div><nav class="side-nav">' + items.map(function (item) {
      return '<a class="side-link ' + (active === item[0] ? 'active' : '') + '" href="' + (FILES[item[0]] || '#') + '">' + icon(item[2]) + '<span>' + item[1] + '</span>' + ((item[0] === 'requests' || item[0] === 'quotes' || item[0] === 'clientConversations') ? '<span class="count">2</span>' : '') + '</a>';
    }).join('') + '</nav>';
  }

  function clientSidebar(active) {
    return '<aside class="sidebar light">' + brand('Client') +
      '<section class="profile-card">' + avatar(47, 'avatar-lg') + '<h3>Sophie Martin</h3><p>Cliente depuis mai 2024</p><span class="verified">✓ Compte vérifié</span></section>' +
      clientNav(active) +
      '<div class="nav-label">Mon compte</div><nav class="side-nav"><a class="side-link" href="#">' + icon('user') + 'Informations personnelles</a><a class="side-link" href="#">' + icon('pin') + 'Adresses</a><a class="side-link" href="#">' + icon('wallet') + 'Moyens de paiement</a><a class="side-link" href="#">' + icon('bell') + 'Notifications</a></nav>' +
      '<section class="support-card"><strong>Besoin d’aide ?</strong><p>Notre équipe est là pour vous aider.</p>' + button('Contacter le support', 'btn-block', '', 'data-toast="Le support a été informé"') + '</section>' +
    '</aside>';
  }

  function clientShell(content, options) {
    options = options || {};
    return '<div class="client-shell">' + clientSidebar(options.active || '') +
      topbar({ dark: !!options.darkTop, search: !!options.darkTop, context: options.context || '', client: true }) +
      '<main class="workspace"><div class="page wide">' + content + '</div></main></div>';
  }

  function publicHeader(client) {
    return '<header class="public-topbar ' + (client ? 'client-header' : '') + '">' + brand(client ? 'Prestataires' : 'Prestataires') +
      '<label class="top-search"><input type="search" placeholder="Rechercher un prestataire, une activité, un service...">' + icon('search') + '</label>' +
      '<nav class="public-nav"><a href="#">Prestataires⌄</a><a href="' + FILES.categories + '">Catégories⌄</a><a href="#">Bons plans</a><a href="' + FILES.favorites + '">' + icon('heart') + ' Mes favoris</a><a href="#">Connexion</a><a class="btn btn-primary" href="' + FILES.verification + '">Proposer une prestation</a></nav></header>';
  }

  function publicShell(content, options) {
    options = options || {};
    return '<div>' + publicHeader(!!options.light) + '<main class="public-main">' + content + '</main></div>';
  }

  function pageHead(title, subtitle, actions) {
    return '<header class="page-head"><div><h1>' + title + '</h1><p>' + subtitle + '</p></div><div class="page-head-actions">' + (actions || '') + '</div></header>';
  }

  function tabs(items, active, className) {
    return '<nav class="tabs ' + (className || '') + '">' + items.map(function (item) {
      var key = Array.isArray(item) ? item[0] : item;
      var label = Array.isArray(item) ? item[1] : item;
      var href = Array.isArray(item) && item[2] ? item[2] : '#';
      return '<a class="tab ' + (key === active ? 'active' : '') + '" href="' + href + '" data-tab="' + key + '">' + label + '</a>';
    }).join('') + '</nav>';
  }

  function settingsTabs(active, compact) {
    var items = compact ? [
      ['profile', icon('user') + ' Mon profil', '#'],
      ['companyInfo', icon('building') + ' Mon entreprise', FILES.companyInfo],
      ['servicesSettings', icon('settings') + ' Services', FILES.servicesSettings],
      ['zones', icon('pin') + ' Zones d’intervention', FILES.zones],
      ['availability', icon('calendar') + ' Disponibilités', '#'],
      ['notifications', icon('bell') + ' Notifications', FILES.notifications],
      ['security', icon('shield') + ' Sécurité', FILES.security]
    ] : [
      ['companyInfo', 'Informations', FILES.companyInfo],
      ['servicesZones', 'Services et zones', FILES.servicesZones],
      ['photos', 'Photos et réalisations', FILES.photos],
      ['contacts', 'Communication & Contacts', FILES.messages],
      ['quoteSettings', 'Devis et paiement', FILES.quoteSettings],
      ['security', 'Sécurité', FILES.security],
      ['notifications', 'Notifications', FILES.notifications],
      ['preferences', 'Préférences', FILES.clientSettings]
    ];
    return tabs(items, active, 'settings-tabs');
  }

  function metrics(items, extraClass) {
    return '<section class="metrics ' + (extraClass || '') + '">' + items.map(function (item) {
      return '<article class="metric ' + (item.color || '') + '"><span class="metric-icon">' + icon(item.icon || 'chart', 'icon-lg') + '</span><div><small>' + item.title + '</small><strong>' + item.value + '</strong><p class="' + (item.trend ? 'trend-up' : '') + '">' + (item.note || '') + '</p></div></article>';
    }).join('') + '</section>';
  }

  function lineChart(options) {
    options = options || {};
    var one = options.one || '0,175 45,148 90,115 135,128 180,165 225,150 270,90 315,118 360,80 405,135 450,120 495,155 540,90 585,100 630,60 675,78 720,35 765,115 810,92';
    var two = options.two;
    return '<div class="chart"><svg viewBox="0 0 820 210" preserveAspectRatio="none">' +
      '<defs><linearGradient id="areaBlue" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0a61ff" stop-opacity=".25"/><stop offset="1" stop-color="#0a61ff" stop-opacity=".02"/></linearGradient></defs>' +
      '<g class="chart-grid"><line x1="0" y1="30" x2="820" y2="30"/><line x1="0" y1="75" x2="820" y2="75"/><line x1="0" y1="120" x2="820" y2="120"/><line x1="0" y1="165" x2="820" y2="165"/><line x1="0" y1="205" x2="820" y2="205"/></g>' +
      '<polygon points="' + one + ' 810,205 0,205" fill="url(#areaBlue)"/>' +
      '<polyline points="' + one + '" fill="none" stroke="' + (options.color || '#075bff') + '" stroke-width="3"/>' +
      (two ? '<polyline points="' + two + '" fill="none" stroke="#0aae6b" stroke-width="2.5"/>' : '') +
      '</svg><div class="chart-labels"><span>20 avr.</span><span>27 avr.</span><span>4 mai</span><span>11 mai</span><span>18 mai</span></div></div>';
  }

  function donut(value, label) {
    return '<div class="donut"><div class="donut-value">' + value + '<small>' + (label || 'Total') + '</small></div></div>';
  }

  function filters(placeholder, selects) {
    return '<div class="filters"><label class="search-wrap">' + icon('search', 'icon-sm') + '<input class="search-field" type="search" placeholder="' + placeholder + '" data-table-search></label>' +
      (selects || []).map(function (selectText) { return '<select><option>' + selectText + '</option></select>'; }).join('') +
      button('Filtres', '', 'filter', 'data-toast="Filtres mis à jour"') + '</div>';
  }

  function mapBox(mode) {
    var circles = mode === 'circles'
      ? '<span class="map-circle" style="width:210px;height:210px;left:31%;top:27%"></span><span class="map-circle green" style="width:145px;height:145px;left:41%;top:4%"></span><span class="map-circle orange" style="width:130px;height:130px;left:54%;top:60%"></span><span class="map-circle violet" style="width:115px;height:115px;left:6%;top:18%"></span>'
      : '<span class="map-polygon"></span>';
    return '<div class="map-box">' + circles + '<span class="map-pin" style="left:49%;top:49%"></span><span class="map-label" style="left:48%;top:58%">Lyon</span><span class="map-label" style="left:64%;top:29%">Villeurbanne</span><span class="map-label" style="left:25%;top:39%">Tassin-la-Demi-Lune</span><span class="map-label" style="left:61%;top:68%">Vénissieux</span></div>';
  }

  function photo(seed, alt) {
    return '<img src="https://picsum.photos/seed/' + seed + '/420/250" alt="' + (alt || '') + '">';
  }

  function detailLine(label, value) {
    return '<div class="detail-line"><span>' + label + '</span><strong>' + value + '</strong></div>';
  }

  window.TrouveMoiUI = {
    FILES: FILES,
    icon: icon,
    avatar: avatar,
    badge: badge,
    switcher: switcher,
    button: button,
    providerShell: providerShell,
    clientShell: clientShell,
    publicShell: publicShell,
    pageHead: pageHead,
    settingsTabs: settingsTabs,
    tabs: tabs,
    metrics: metrics,
    lineChart: lineChart,
    donut: donut,
    filters: filters,
    mapBox: mapBox,
    photo: photo,
    detailLine: detailLine
  };
})();

(function () {
  'use strict';
  var pages = window.TrouveMoiPages = window.TrouveMoiPages || {};

  function toast(message) {
    var node = document.querySelector('.toast');
    if (!node) {
      node = document.createElement('div');
      node.className = 'toast';
      document.body.appendChild(node);
    }
    node.textContent = message || 'Action effectuée';
    node.classList.add('show');
    clearTimeout(window.__trouvemoiToast);
    window.__trouvemoiToast = setTimeout(function () { node.classList.remove('show'); }, 2200);
  }

  function bindInteractions() {
    document.querySelectorAll('.mobile-menu').forEach(function (button) {
      button.addEventListener('click', function () { document.body.classList.toggle('sidebar-open'); });
    });

    document.querySelectorAll('.switch').forEach(function (node) {
      function toggle() {
        node.classList.toggle('on');
        node.setAttribute('aria-checked', node.classList.contains('on') ? 'true' : 'false');
      }
      node.addEventListener('click', toggle);
      node.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          toggle();
        }
      });
    });

    document.querySelectorAll('[data-tab]').forEach(function (tab) {
      tab.addEventListener('click', function (event) {
        if (tab.getAttribute('href') === '#') event.preventDefault();
        var nav = tab.closest('.tabs');
        if (nav) nav.querySelectorAll('.tab').forEach(function (item) { item.classList.remove('active'); });
        tab.classList.add('active');
      });
    });

    document.querySelectorAll('[data-toast]').forEach(function (button) {
      button.addEventListener('click', function (event) {
        if (button.tagName === 'BUTTON') event.preventDefault();
        toast(button.getAttribute('data-toast'));
      });
    });

    document.querySelectorAll('[data-composer]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var input = form.querySelector('input[name="message"]');
        var container = form.closest('.chat').querySelector('[data-chat-messages]');
        if (!input || !container || !input.value.trim()) return;
        var bubble = document.createElement('div');
        bubble.className = 'bubble mine';
        bubble.innerHTML = input.value.replace(/[<>]/g, '') + '<time>À l’instant ✓</time>';
        container.appendChild(bubble);
        container.scrollTop = container.scrollHeight;
        input.value = '';
      });
    });

    document.querySelectorAll('.conversation-item').forEach(function (item) {
      item.addEventListener('click', function () {
        document.querySelectorAll('.conversation-item').forEach(function (row) { row.classList.remove('active'); });
        item.classList.add('active');
      });
    });

    document.querySelectorAll('.favorite-heart').forEach(function (button) {
      button.addEventListener('click', function () {
        button.classList.toggle('text-red');
        toast(button.classList.contains('text-red') ? 'Retiré des favoris' : 'Ajouté aux favoris');
      });
    });

    document.querySelectorAll('[data-table-search]').forEach(function (input) {
      input.addEventListener('input', function () {
        var card = input.closest('.card');
        if (!card) return;
        var query = input.value.trim().toLowerCase();
        card.querySelectorAll('tbody tr').forEach(function (row) {
          row.hidden = query && row.textContent.toLowerCase().indexOf(query) === -1;
        });
      });
    });

    document.querySelectorAll('tbody tr').forEach(function (row) {
      row.addEventListener('click', function () {
        var body = row.parentElement;
        if (body) body.querySelectorAll('tr').forEach(function (item) { item.classList.remove('selected'); });
        row.classList.add('selected');
      });
    });
  }

  function render() {
    var root = document.getElementById('app');
    var key = document.body.getAttribute('data-page');
    if (!root) return;
    if (!pages[key]) {
      root.innerHTML = '<main class="public-main"><section class="card card-pad"><h1>Page introuvable</h1><p>La vue demandée n’est pas disponible.</p></section></main>';
      return;
    }
    root.innerHTML = pages[key]();
    bindInteractions();
  }

  document.addEventListener('DOMContentLoaded', render);
})();

(function () {
  'use strict';
  var U = window.TrouveMoiUI;
  var F = U.FILES;
  var I = U.icon;
  var A = U.avatar;
  var B = U.badge;
  var S = U.switcher;
  var Btn = U.button;
  var P = window.TrouveMoiPages = window.TrouveMoiPages || {};

  function barChart() {
    var bars = [28,38,46,54,47,59,33,42,55,37,49,31,25,58,70,35,28,18,30,41,51,35,27,20,45,42,38,33,47,29,51,55,31,20,16];
    return '<div class="chart"><svg viewBox="0 0 820 210" preserveAspectRatio="none"><g class="chart-grid"><line x1="0" y1="30" x2="820" y2="30"/><line x1="0" y1="75" x2="820" y2="75"/><line x1="0" y1="120" x2="820" y2="120"/><line x1="0" y1="165" x2="820" y2="165"/></g>' + bars.map(function(v,n){return '<rect x="'+(n*23+6)+'" y="'+(195-v*2.1)+'" width="10" height="'+(v*2.1)+'" rx="3" fill="none" stroke="#9a39ff" stroke-width="3"/>';}).join('') + '</svg><div class="chart-labels"><span>16 avr</span><span>26 avr</span><span>6 mai</span><span>16 mai</span></div></div>';
  }

  P.statistics = function () {
    var content = U.pageHead('Statistiques','Suivez les performances de votre activité sur TrouveMoi.',Btn('Exporter','','download','data-toast="Rapport exporté"')) +
      U.metrics([
        {title:'Vues de profil',value:'12 458',note:'+18%',icon:'eye',trend:true},
        {title:'Demandes reçues',value:'356',note:'+22%',icon:'message',color:'violet',trend:true},
        {title:'Devis envoyés',value:'118',note:'+15%',icon:'file',color:'orange',trend:true},
        {title:'Chantiers réalisés',value:'47',note:'+12%',icon:'check',color:'green',trend:true},
        {title:'Note moyenne',value:'4,8/5',note:'+0,1',icon:'star',color:'orange',trend:true}
      ]) +
      '<div class="grid cols-2"><section class="card"><div class="card-head"><h3>'+I('eye')+' Vues de profil</h3>'+B('Par jour','blue')+'</div>'+U.lineChart({})+'</section><section class="card"><div class="card-head"><h3>'+I('message')+' Demandes reçues</h3>'+B('Par jour','violet')+'</div>'+barChart()+'</section></div>' +
      '<div class="grid cols-3 mt-14"><section class="card card-pad"><h3>Demandes par catégorie</h3><div style="display:flex;align-items:center;gap:25px;margin-top:22px">'+U.donut('356','demandes')+'<div>'+cat('Plomberie','142 (40%)','#075bff')+cat('Chauffage','86 (24%)','#7b35ef')+cat('Salle de bain','62 (17%)','#715366')+cat('Débouchage','34 (10%)','#ff9d00')+'</div></div></section><section class="card card-pad"><h3>Taux de conversion</h3><div style="margin-top:20px"><div style="height:48px;clip-path:polygon(0 0,100% 0,90% 100%,10% 100%);background:#075bff"></div><div style="height:43px;width:80%;margin:auto;clip-path:polygon(0 0,100% 0,87% 100%,13% 100%);background:#7b35ef"></div><div style="height:38px;width:60%;margin:auto;clip-path:polygon(0 0,100% 0,84% 100%,16% 100%);background:#ff9d00"></div><div style="height:34px;width:42%;margin:auto;clip-path:polygon(0 0,100% 0,80% 100%,20% 100%);background:#0aa565"></div></div>'+U.detailLine('Demandes reçues','356')+U.detailLine('Devis envoyés','118 (33%)')+U.detailLine('Devis acceptés','73 (20%)')+U.detailLine('Chantiers réalisés','47 (13%)')+'</section><section class="card"><div class="card-head"><h3>Évolution du chiffre d’affaires</h3>'+B('Par jour','blue')+'</div>'+U.lineChart({color:'#0aa565',one:'0,180 45,145 90,158 135,120 180,105 225,130 270,95 315,115 360,80 405,100 450,75 495,92 540,60 585,78 630,42 675,61 720,35 765,50 810,22'})+'<div class="grid cols-2 card-pad"><div><small>Chiffre d’affaires</small><strong style="font-size:20px">18 560 €</strong><p class="trend-up">+16%</p></div><div><small>Panier moyen</small><strong style="font-size:20px">395 €</strong><p class="trend-up">+8%</p></div></div></section></div>' +
      '<section class="card card-pad mt-14"><div class="grid cols-3"><div><h3>Avis clients</h3><div style="font:800 38px Manrope;margin-top:20px">4,8<small>/5</small> <span class="stars" style="font-size:20px">★★★★★</span></div><p class="text-muted">Note moyenne sur la période</p></div><div>'+rating('5 étoiles',73)+rating('4 étoiles',20)+rating('3 étoiles',5)+rating('2 étoiles',1)+rating('1 étoile',1)+'</div><div><h3>Avis les plus récents</h3>'+miniReview(47,'Sophie Martin','Très satisfaite du travail réalisé !')+miniReview(13,'Michel Dupont','Bonne prestation, technicien compétent.')+'</div></div></section>';
    return U.providerShell(content,{active:'statistics',includeSettings:true,darkTop:true,wide:true});
  };

  function cat(name,value,color) { return '<div class="detail-line"><span><i class="legend-dot" style="background:'+color+'"></i>'+name+'</span><strong>'+value+'</strong></div>'; }
  function rating(label,pct) { return '<div style="display:grid;grid-template-columns:70px 1fr 36px;gap:8px;align-items:center;margin:8px 0;font-size:10px"><span>'+label+'</span><div class="progress orange"><span style="width:'+pct+'%"></span></div><strong>'+pct+'%</strong></div>'; }
  function miniReview(id,name,text) { return '<div class="list-row">'+A(id,'avatar-sm')+'<div class="list-main"><strong>'+name+' <span class="stars">★★★★★</span></strong><p>'+text+'</p></div></div>'; }

  P.messages = function () {
    var people = [
      [47,'Sophie Martin','Bonjour, est-ce que vous êtes disponible...','10:30','2'],
      [13,'Michel Dupont','Merci pour votre devis, je vais...','Hier','1'],
      [45,'Laura Bernard','Parfait, merci pour votre réactivité !','Hier',''],
      [15,'Thomas Leroy','Pouvez-vous passer demain matin ?','12/05',''],
      [32,'Camille Petit','Très bien, merci beaucoup.','11/05',''],
      [12,'Julien Moreau','D’accord, je vous confirme ça ?','10/05',''],
      [47,'Nathalie Blanc','Merci pour votre intervention.','09/05','']
    ];
    var list = people.map(function(r,n){return '<div class="conversation-item '+(n===0?'active':'')+'">'+A(r[0])+'<div class="list-main"><strong>'+r[1]+'</strong><p>'+r[2]+'</p></div><span class="conversation-time">'+r[3]+(r[4]?'<b style="display:block;margin:6px auto;background:var(--blue);color:#fff;border-radius:50%;width:20px;height:20px;text-align:center;line-height:20px">'+r[4]+'</b>':'')+'</span></div>';}).join('');
    var bubbles = [
      ['Bonjour, je souhaite rénover ma salle de bain et je voudrais avoir un devis.','10:27',false],
      ['Bonjour Mme Martin, merci pour votre message. Je suis disponible pour échanger sur votre projet. Pouvez-vous me donner quelques détails sur vos besoins ?','10:28',true],
      ['La salle de bain fait environ 6m². Je voudrais une douche à l’italienne, un meuble vasque et un sèche-serviette.','10:29',false],
      ['Merci pour ces informations. Je peux vous proposer un devis détaillé. Seriez-vous disponible cette semaine ?','10:30',true],
      ['Oui, jeudi après-midi serait parfait !','10:31',false],
      ['Parfait, je vous envoie une proposition.','10:31',true]
    ].map(function(m){return '<div class="bubble '+(m[2]?'mine':'')+'">'+m[0]+'<time>'+m[1]+(m[2]?' ✓✓':'')+'</time></div>';}).join('');
    var content = U.pageHead('Messages','Échangez avec vos clients en toute simplicité.') +
      '<div class="messages-layout"><section class="conversation-list"><div class="filters"><label class="search-wrap">'+I('search','icon-sm')+'<input class="search-field" placeholder="Rechercher une conversation..."></label></div>'+U.tabs([['all','Toutes'],['unread','Non lues 8']],'all')+list+'</section><section class="chat"><header class="chat-head">'+A(47)+'<div class="list-main"><strong>Sophie Martin</strong><p>Rénovation salle de bain · En ligne</p></div><button class="icon-btn">'+I('phone','icon-sm')+'</button><button class="icon-btn">'+I('camera','icon-sm')+'</button><button class="icon-btn">'+I('more','icon-sm')+'</button></header><div class="chat-messages" data-chat-messages>'+bubbles+'</div><form class="composer" data-composer><input name="message" autocomplete="off" placeholder="Écrire un message..."><button class="btn btn-primary">'+I('send')+'</button></form></section><aside class="conversation-detail"><h3>Détails de la conversation</h3><section class="card card-pad mt-14"><div class="list-row">'+A(47)+'<div class="list-main"><strong>Sophie Martin</strong><p>Client depuis mai 2024</p></div></div>'+Btn('Voir le profil','btn-block','','data-toast="Profil ouvert"')+'</section><section class="card card-pad"><h3>Informations</h3>'+line('mail','sophie.martin@email.fr')+line('phone','06 12 34 56 78')+line('pin','15 rue des Fleurs, 69003 Lyon')+'</section><section class="card card-pad"><h3>Dernière demande</h3><p>Rénovation salle de bain</p>'+B('En cours','orange')+'</section><section class="card card-pad">'+Btn('Créer un devis','btn-block','file','data-toast="Nouveau devis créé"')+Btn('Voir la demande','btn-block mt-14','inbox','data-toast="Demande ouverte"')+Btn('Bloquer le client','btn-danger btn-block mt-14','x','data-toast="Client bloqué"')+'</section></aside></div>';
    return U.providerShell(content,{active:'messages',includeSettings:true,darkTop:true,wide:true});
  };

  function line(iconName,text) { return '<div class="list-row"><span class="text-blue">'+I(iconName)+'</span><div class="list-main"><strong>'+text+'</strong></div></div>'; }

  P.zones = function () {
    var zones = [
      ['Lyon et alentours','Lyon, Villeurbanne, Bron, Caluire-et-Cuire, Sainte-Foy-lès-Lyon','25 km','blue'],
      ['Villefranche-sur-Saône et alentours','Villefranche-sur-Saône, Gleizé, Arnas, Trévoux','20 km','green'],
      ['Vienne et alentours','Vienne, Pont-Évêque, Chasse-sur-Rhône','20 km','orange'],
      ['Tarare et alentours','Tarare, Amplepuis, Les Sauvages','25 km','violet']
    ];
    var content = U.pageHead('Zones d’intervention','Définissez les zones géographiques dans lesquelles vous proposez vos services.') + U.settingsTabs('zones',true) +
      '<div class="grid layout-3-2"><section class="card card-pad"><div class="page-head"><div><h3>Mes zones d’intervention</h3><p>Ajoutez, modifiez ou supprimez les zones où vous intervenez.</p></div>'+Btn('Ajouter une zone','btn-primary','plus','data-toast="Nouvelle zone ajoutée"')+'</div><div class="section-list" style="padding:0">'+zones.map(function(r,n){return '<div class="list-row"><span class="list-icon" style="color:var(--'+r[3]+')">'+I('pin')+'</span><div class="list-main"><strong>'+r[0]+' '+(n===0?B('Zone principale','blue'):'')+'</strong><p>'+r[1]+'</p></div>'+B('Rayon : '+r[2],'')+S(true,'green')+'<button class="icon-btn">'+I('more','icon-sm')+'</button></div>';}).join('')+'</div><div class="info-note">'+I('help')+'Les clients peuvent vous trouver uniquement si leur adresse se situe dans une zone active.</div></section><section class="card card-pad"><h3>Aperçu de vos zones d’intervention</h3><div class="mt-14">'+U.mapBox('circles')+'</div></section></div>' +
      '<section class="card card-pad mt-14"><h3>Ajouter une zone d’intervention</h3><div class="grid cols-3 mt-14">'+zoneChoice('map','Par rayon autour d’un point','Définissez un rayon autour d’une ville ou d’une adresse.')+zoneChoice('pin','Par sélection de villes / codes postaux','Sélectionnez plusieurs villes ou codes postaux.')+zoneChoice('settings','Zone personnalisée sur la carte','Dessinez votre zone directement sur la carte.')+'</div></section><div style="text-align:right;margin-top:18px">'+Btn('Enregistrer les modifications','btn-primary','','data-toast="Zones enregistrées"')+'</div>';
    return U.providerShell(content,{active:'settings',context:'Paramètres',wide:true});
  };
  function zoneChoice(iconName,title,text){return '<div class="list-row"><span class="list-icon">'+I(iconName)+'</span><div class="list-main"><strong>'+title+'</strong><p>'+text+'</p></div>'+I('chevron','icon-sm')+'</div>';}

  P.servicesZones = function () {
    var services = [
      ['Plomberie générale','Installation, réparation et entretien de vos équipements.','wrench',true],
      ['Dépannage plomberie','Intervention rapide pour toutes vos urgences plomberie.','settings',true],
      ['Installation sanitaire','Pose et remplacement de vos sanitaires.','briefcase',true],
      ['Chauffe-eau','Installation, réparation et entretien de chauffe-eau.','wallet',true],
      ['Recherche de fuite','Détection et réparation de fuites d’eau.','pin',true],
      ['Rénovation salle de bain','Rénovation complète ou partielle de votre salle de bain.','image',true],
      ['Débouchage canalisation','Débouchage de vos canalisations et évacuations.','wrench',true],
      ['Autres services','Autres prestations sur demande.','more',false]
    ];
    var content = U.pageHead('Paramètres','Accueil  ›  Paramètres  ›  Services et zones') + U.settingsTabs('servicesZones',false) +
      '<div class="grid layout-1-2"><div><section class="card card-pad"><h3>Mes services</h3><p class="card-subtitle">Sélectionnez les services que vous proposez à vos clients.</p><div class="section-list" style="padding:0">'+services.map(function(r){return '<div class="list-row"><span class="list-icon">'+I(r[2])+'</span><div class="list-main"><strong>'+r[0]+'</strong><p>'+r[1]+'</p></div><input type="checkbox" '+(r[3]?'checked':'')+' style="accent-color:var(--blue)"></div>';}).join('')+'</div>'+Btn('Ajouter un service','btn-block mt-14','plus','data-toast="Service ajouté"')+'</section><div class="info-note">'+I('help')+'Plus vos services et zones sont précis, plus vous avez de chances d’être contacté.</div></div>' +
      '<section class="card card-pad"><div class="page-head"><div><h3>Ma zone d’intervention</h3><p>Définissez les zones géographiques dans lesquelles vous intervenez.</p></div>'+Btn('Dessiner ma zone','','edit','data-toast="Mode dessin activé"')+'</div><label class="search-wrap"><span>'+I('search','icon-sm')+'</span><input class="search-field" placeholder="Rechercher une ville ou un code postal..."></label><div class="mt-14">'+U.mapBox('polygon')+'</div><h3 style="margin-top:14px">Villes et zones incluses (12)</h3><p>'+['Lyon 3e','Lyon 6e','Villeurbanne','Bron','Vénissieux','Oullins','Tassin-la-Demi-Lune','Sainte-Foy-lès-Lyon','Écully','Meyzieu','Genas','Saint-Priest'].map(function(x){return B(x+' ×','blue');}).join(' ')+'</p><h3>Rayon d’intervention</h3><input type="range" min="5" max="50" value="20" style="width:90%;accent-color:var(--blue)"> <strong>20 km</strong><div class="detail-line"><strong>Afficher ma zone sur ma fiche publique</strong>'+S(true)+'</div><div style="text-align:right;margin-top:18px">'+Btn('Enregistrer les modifications','btn-primary','','data-toast="Services et zones enregistrés"')+'</div></section></div>';
    return U.providerShell(content,{active:'servicesZones',includeSettings:true,darkTop:true,wide:true});
  };

  P.favorites = function () {
    var providers = [
      ['plumber','Plomberie Express','Plombier','Lyon (69)','4,9 (256 avis)'],
      ['cleaning','Clean & Shine','Service de nettoyage','Toulouse (31)','4,8 (178 avis)'],
      ['electrician','Elec Solutions','Électricien','Nantes (44)','4,7 (142 avis)'],
      ['garden','Jardin Vert','Jardinier paysagiste','Bordeaux (33)','4,8 (98 avis)'],
      ['computer','Help Informatique','Assistance informatique','Lille (59)','4,9 (211 avis)'],
      ['moving','Déménagement Facile','Déménagement','Marseille (13)','4,6 (124 avis)']
    ];
    var content = '<div class="breadcrumb">Accueil  ›  Mes favoris</div>' + U.pageHead('♡  Mes favoris','Retrouvez ici tous vos prestataires, catégories et bons plans favoris.') +
      U.metrics([
        {title:'Tous mes favoris',value:'24',icon:'heart'},
        {title:'Prestataires',value:'16',icon:'user'},
        {title:'Catégories',value:'6',icon:'settings',color:'green'},
        {title:'Bons plans',value:'2',icon:'star',color:'red'},
        {title:'Articles',value:'0',icon:'file'}
      ]) +
      '<div class="grid" style="grid-template-columns:230px minmax(0,1fr)"><aside class="card card-pad"><h2>Filtres</h2><div class="separator"></div><div class="form-group"><label>Trier par</label><select><option>Date d’ajout (récent)</option></select></div><h3 style="margin-top:22px">Type</h3>'+['Tous les favoris','Prestataires','Catégories','Bons plans','Articles'].map(function(x){return '<label class="check-row"><input type="checkbox" checked><strong>'+x+'</strong></label>';}).join('')+'<h3 style="margin-top:22px">Catégories</h3>'+['Maison & Travaux','Nettoyage','Jardinage','Informatique','Bien-être','Cours & Soutien'].map(function(x){return '<label class="check-row"><input type="checkbox"><span>'+x+'</span></label>';}).join('')+Btn('Effacer les filtres','btn-block mt-14','x','data-toast="Filtres effacés"')+'</aside>' +
      '<div><div class="page-head"><h2>Prestataires favoris (16)</h2><a class="text-blue fw-700" href="#">Voir tous les prestataires favoris →</a></div><div class="favorite-grid">'+providers.map(function(r,n){return '<article class="favorite-card"><div style="position:relative">'+U.photo(r[0])+'<button class="icon-btn favorite-heart" style="position:absolute;right:7px;top:7px;color:var(--blue)">'+I('heart','icon-sm')+'</button></div><div class="body"><h3>'+r[1]+'</h3><p>'+r[2]+'</p><p><span class="stars">★</span> '+r[4]+'</p><p>'+r[3]+'</p><p>Ajouté le '+(12-n)+'/05/2024</p></div></article>';}).join('')+'</div><div class="page-head" style="margin-top:25px"><h2>Catégories favorites (6)</h2></div><div class="grid cols-3">'+favoriteCat('Maison & Travaux','28 456 prestataires','home','violet')+favoriteCat('Nettoyage','8 934 prestataires','settings','blue')+favoriteCat('Jardinage','6 781 prestataires','star','green')+favoriteCat('Informatique','12 543 prestataires','briefcase','violet')+favoriteCat('Bien-être','4 122 prestataires','heart','red')+favoriteCat('Cours & Soutien','3 876 prestataires','file','orange')+'</div><div class="page-head" style="margin-top:25px"><h2>Bons plans favoris (2)</h2></div><div class="grid cols-2"><section class="card" style="display:flex;overflow:hidden"><div class="photo" style="width:180px;border-radius:0">'+U.photo('clean-deal')+'</div><div class="card-pad"><h3>Service de nettoyage complet</h3><p>Clean & Shine</p><strong style="font-size:18px">69,99 €</strong></div></section><section class="card" style="display:flex;overflow:hidden"><div class="photo" style="width:180px;border-radius:0">'+U.photo('garden-deal')+'</div><div class="card-pad"><h3>Taille de haie & entretien jardin</h3><p>Jardin Vert</p><strong style="font-size:18px">59,00 €</strong></div></section></div></div></div>';
    return U.publicShell(content,{light:true});
  };
  function favoriteCat(title,count,iconName,color){return '<section class="card card-pad" style="background:var(--blue-soft)"><span class="list-icon" style="color:var(--'+color+')">'+I(iconName)+'</span><h3 style="margin-top:12px">'+title+'</h3><p class="text-muted">'+count+'</p></section>';}

  P.photos = function () {
    var shots = ['bathroom','pipes','boiler','radiator','sink','shower','floor','ac','tools','plumber','kitchen','copper'];
    var projects = [
      ['bathroom','Rénovation complète salle de bain','Salle de bain','Lyon 3e','15/05/2024'],
      ['boiler','Installation chaudière à condensation','Chauffage','Villeurbanne','02/05/2024'],
      ['sink','Création réseau eau cuisine','Plomberie générale','Écully','18/04/2024'],
      ['drain','Débouchage canalisation principale','Débouchage','Bron','10/04/2024'],
      ['radiator','Remplacement radiateurs','Chauffage','Sainte-Foy-lès-Lyon','28/03/2024']
    ];
    var content = U.pageHead('Paramètres','Accueil  ›  Paramètres  ›  Photos et réalisations') + U.settingsTabs('photos',false) +
      '<div class="grid layout-1-2"><section class="card card-pad"><h2>Galerie photos</h2><p class="card-subtitle">Ajoutez des photos de qualité pour mettre en valeur votre travail.</p><div class="upload-zone mt-14"><div>'+I('upload','icon-lg')+'<strong style="display:block">Ajouter des photos</strong><p>Glissez-déposez vos fichiers ici ou cliquez pour sélectionner</p></div></div><h3 style="margin:18px 0 10px">Photos ajoutées (12/50)</h3><div class="gallery">'+shots.map(function(seed){return '<div class="photo">'+U.photo(seed)+'<button class="icon-btn">'+I('more','icon-sm')+'</button></div>';}).join('')+'</div>'+Btn('Gérer mes albums','btn-block mt-14','file','data-toast="Gestionnaire d’albums ouvert"')+'<div class="info-note">'+I('help')+'Les fiches avec des photos reçoivent 3x plus de demandes.</div></section>' +
      '<div><section class="card card-pad"><div class="page-head"><div><h2>Mes réalisations</h2><p>Mettez en avant vos meilleurs projets réalisés.</p></div>'+Btn('Ajouter une réalisation','','plus','data-toast="Nouvelle réalisation créée"')+'</div><div class="section-list" style="padding:0">'+projects.map(function(r){return '<div class="list-row card" style="margin-top:9px;padding:8px"><span class="photo" style="width:105px;aspect-ratio:1.5">'+U.photo(r[0])+'</span><div class="list-main"><strong>'+r[1]+'</strong><p>'+I('briefcase','icon-sm')+' '+r[2]+'<br>'+I('pin','icon-sm')+' '+r[3]+'</p></div><span class="text-muted">'+r[4]+'</span><button class="icon-btn">'+I('more','icon-sm')+'</button></div>';}).join('')+'</div><div class="center">'+Btn('Voir toutes mes réalisations','','','data-toast="Toutes les réalisations affichées"')+'</div></section><section class="card card-pad"><h3>Allez plus loin</h3><div class="detail-line"><div><strong>Afficher la galerie photos sur ma fiche publique</strong><br><small class="text-muted">Vos photos seront visibles par vos clients potentiels.</small></div>'+S(true)+'</div><div class="detail-line"><div><strong>Afficher mes réalisations sur ma fiche publique</strong><br><small class="text-muted">Vos réalisations seront visibles par vos clients potentiels.</small></div>'+S(true)+'</div><div style="text-align:right">'+Btn('Enregistrer les modifications','btn-primary','','data-toast="Galerie enregistrée"')+'</div></section></div></div>';
    return U.providerShell(content,{active:'photos',includeSettings:true,darkTop:true,wide:true});
  };

  P.security = function () {
    var content = U.pageHead('Sécurité','Protégez votre compte et vos données personnelles.') + U.settingsTabs('security',true) +
      '<div class="grid cols-2"><div><section class="card card-pad"><h3>Sécuriser votre compte</h3><p class="card-subtitle">Renforcez la sécurité de votre compte pour le protéger contre les accès non autorisés.</p>'+securityRow('lock','Mot de passe','Dernière modification : 15 avril 2024',Btn('Modifier','btn-sm','','data-toast="Modification du mot de passe ouverte"'))+securityRow('shield','Authentification à deux facteurs (2FA)','Ajoutez une couche de sécurité supplémentaire.',B('Activée','green'))+securityRow('computer','Sessions actives','Gérez les appareils connectés à votre compte.',B('3 sessions actives','blue'))+'</section><section class="card card-pad"><h3>Vérification et confidentialité</h3>'+securityRow('check','Compte vérifié','Votre identité a été vérifiée.',B('Vérifié','green'))+securityRow('user','Données personnelles','Gérez vos informations personnelles.',Btn('Gérer','btn-sm','','data-toast="Données personnelles ouvertes"'))+securityRow('shield','Confidentialité','Choisissez qui peut voir vos informations.',Btn('Gérer','btn-sm','','data-toast="Confidentialité ouverte"'))+'</section><section class="card card-pad"><h3>Actions de sécurité</h3>'+securityRow('x','Se déconnecter de tous les appareils','Déconnectez toutes les sessions.',Btn('Déconnecter','btn-danger btn-sm','','data-toast="Sessions déconnectées"'))+securityRow('x','Supprimer mon compte','Supprimez définitivement votre compte.',Btn('Supprimer','btn-danger btn-sm','','data-toast="Confirmation requise"'))+'</section><div class="info-note">'+I('lock')+'Vos données sont chiffrées et sécurisées.</div></div>' +
      '<div><section class="card card-pad"><h3>Activité de sécurité</h3>'+securityRow('settings','Connexion réussie','Lyon, France - Chrome sur Windows','Aujourd’hui à 09:42')+securityRow('lock','Mot de passe modifié','Lyon, France','15 avril 2024')+securityRow('settings','Connexion réussie','Villeurbanne, France - Safari sur iPhone','14 avril 2024')+securityRow('shield','Authentification 2FA activée','Lyon, France','10 avril 2024')+'</section><section class="card card-pad"><h3>Conseils de sécurité</h3>'+securityRow('lock','Utilisez un mot de passe robuste','Évitez les mots courants et utilisez au moins 8 caractères.','')+securityRow('shield','Activez l’authentification à deux facteurs','Cela ajoute une protection supplémentaire.','')+securityRow('shield','Ne partagez jamais vos identifiants','TrouveMoi ne vous demandera jamais votre mot de passe.','')+securityRow('check','Vérifiez régulièrement vos activités','Surveillez les connexions et appareils inconnus.','')+'</section><div style="text-align:right;margin-top:18px">'+Btn('Enregistrer les modifications','btn-primary','','data-toast="Sécurité enregistrée"')+'</div></div></div>';
    return U.providerShell(content,{active:'settings',context:'Paramètres',wide:true});
  };
  function securityRow(iconName,title,text,action){return '<div class="list-row"><span class="list-icon">'+I(ICONSafe(iconName))+'</span><div class="list-main"><strong>'+title+'</strong><p>'+text+'</p></div>'+(typeof action==='string'?action:'')+'</div>';}
  function ICONSafe(name){return name==='computer'?'briefcase':name;}

  P.categories = function () {
    var cats = [
      ['house-night','Maison & Travaux','18 543 services','home'],
      ['move-van','Déménagement','2 845 services','briefcase'],
      ['clean-home','Nettoyage','4 326 services','settings'],
      ['lawn','Jardinage','6 781 services','star'],
      ['coding','Informatique','3 245 services','computer'],
      ['concert','Événementiel','2 194 services','calendar'],
      ['massage','Bien-être','1 987 services','heart'],
      ['course','Cours & Soutien','5 432 services','file'],
      ['car-repair','Réparation auto','3 674 services','settings'],
      ['drill','Petits travaux','7 891 services','wrench'],
      ['photography','Photographie','2 156 services','camera'],
      ['fitness','Coaching & Sport','3 289 services','star'],
      ['hair','Beauté & Coiffure','2 834 services','wrench'],
      ['diy','Bricolage','4 125 services','wrench'],
      ['delivery','Transport & Livraison','3 945 services','briefcase'],
      ['paperwork','Administratif','2 345 services','file'],
      ['graphic','Design & Graphisme','1 876 services','edit']
    ];
    var content = '<div class="breadcrumb">'+I('home','icon-sm')+' &nbsp; Accueil  ›  Catégories</div>' + U.pageHead('Toutes les catégories','Trouvez facilement le prestataire qu’il vous faut parmi toutes nos catégories.') +
      '<div class="grid cols-4 mb-14" style="max-width:760px;margin-left:auto">'+statPill('home','+ 120 000','prestataires','violet')+statPill('shield','Prestataires','vérifiés','green')+statPill('star','Avis clients','contrôlés','orange')+statPill('help','Support 7j/7','à votre écoute','blue')+'</div><section class="category-grid">'+cats.map(function(r){return '<article class="category-card"><div class="category-photo">'+U.photo(r[0])+'<span class="list-icon" style="position:absolute;left:14px;bottom:10px;background:#fff">'+I(r[3])+'</span></div><h3>'+r[1]+'</h3><p>'+r[2]+'</p></article>';}).join('')+'<article class="category-card card-pad" style="display:grid;place-items:center"><div><span class="list-icon">'+I('settings')+'</span><h3>Voir toutes<br>les catégories</h3><p>Encore plus de services</p></div></article></section><section class="card card-pad mt-14" style="display:flex;align-items:center;gap:18px;background:linear-gradient(90deg,#f3efff,#fbf9ff)"><span class="metric-icon" style="width:60px;height:60px;background:#5d2bd7;color:#fff">'+I('star','icon-lg')+'</span><div><h2>Vous êtes prestataire ?</h2><p>Rejoignez des milliers de professionnels et développez votre activité.</p></div><div style="margin-left:auto">'+Btn('En savoir plus','','','data-toast="Informations ouvertes"')+' '+Btn('Devenir prestataire','btn-primary','','data-toast="Inscription prestataire ouverte"')+'</div></section>';
    return U.publicShell(content,{});
  };
  function statPill(iconName,title,text,color){return '<section class="card card-pad" style="display:flex;align-items:center;gap:12px"><span class="metric-icon" style="color:var(--'+color+')">'+I(iconName)+'</span><div><strong>'+title+'</strong><br>'+text+'</div></section>';}
})();

(function () {
  'use strict';
  var U = window.TrouveMoiUI;
  var F = U.FILES;
  var I = U.icon;
  var A = U.avatar;
  var B = U.badge;
  var S = U.switcher;
  var Btn = U.button;
  var P = window.TrouveMoiPages = window.TrouveMoiPages || {};

  function conversationItems(clientMode) {
    var rows = clientMode ? [
      [12,'Plomberie Express','Bonjour, merci pour votre demande...','10:30','2'],
      [13,'Élec Solutions','Nous sommes disponibles mercredi.','Hier','1'],
      [45,'Clean & Shine','Voici le détail de notre intervention.','Hier',''],
      [15,'Jardin Vert','Nous pouvons intervenir vendredi.','12/05',''],
      [32,'Menuiserie Pro','Merci, je reviens vers vous rapidement.','12/05',''],
      [11,'Peinture Déco','Le devis est prêt, merci de vérifier.','09/05','']
    ] : [
      [47,'Sophie Martin','Bonjour, est-ce que vous êtes disponible...','10:30','2'],
      [13,'Michel Dupont','Merci pour votre devis, je vais...','Hier','1'],
      [45,'Laura Bernard','Parfait, merci pour votre réactivité !','Hier',''],
      [15,'Thomas Leroy','Pouvez-vous passer demain matin ?','12/05',''],
      [32,'Camille Petit','Très bien, merci beaucoup.','11/05',''],
      [12,'Julien Moreau','D’accord, je vous confirme ça ?','10/05',''],
      [47,'Nathalie Blanc','Merci pour votre intervention.','09/05','']
    ];
    return rows.map(function (r,n) {
      return '<div class="conversation-item ' + (n===0?'active':'') + '">' + A(r[0]) + '<div class="list-main"><strong>' + r[1] + '</strong><p>' + r[2] + '</p></div><span class="conversation-time">' + r[3] + (r[4]?'<span class="count" style="display:block;background:var(--blue);color:#fff;border-radius:50%;padding:2px 6px;margin-top:6px">'+r[4]+'</span>':'') + '</span></div>';
    }).join('');
  }

  function messageBubbles(clientMode) {
    var client = [
      ['Bonjour Mme Martin, merci pour votre demande de devis pour vos travaux de plomberie.','10:30',false],
      ['Bonjour, oui tout à fait. Pouvez-vous m’envoyer une estimation pour la salle de bain ?','10:31',true],
      ['Bien sûr, je vous envoie cela dans la journée. Avez-vous des photos ou des précisions sur les travaux à réaliser ?','10:32',false]
    ];
    var provider = [
      ['Bonjour, je souhaite rénover ma salle de bain et je voudrais avoir un devis.','10:27',false],
      ['Bonjour Mme Martin, merci pour votre message. Je suis disponible pour échanger sur votre projet. Pouvez-vous me donner quelques détails sur vos besoins ?','10:28',true],
      ['La salle de bain fait environ 6m². Je voudrais une douche à l’italienne, un meuble vasque et un sèche-serviette.','10:29',false],
      ['Merci pour ces informations. Je peux vous proposer un devis détaillé. Seriez-vous disponible cette semaine ?','10:30',true],
      ['Oui, jeudi après-midi serait parfait !','10:31',false],
      ['Parfait, je vous envoie une proposition.','10:31',true]
    ];
    return (clientMode ? client : provider).map(function (m) {
      return '<div class="bubble ' + (m[2]?'mine':'') + '">' + m[0] + '<time>' + m[1] + (m[2]?' ✓✓':'') + '</time></div>';
    }).join('');
  }

  function messagesLayout(clientMode) {
    return '<div class="messages-layout"><section class="conversation-list"><div class="filters"><label class="search-wrap">' + I('search','icon-sm') + '<input class="search-field" placeholder="Rechercher une conversation..."></label></div>' + U.tabs([['all','Toutes'],['unread','Non lues  2'],['archive','Archivées']], 'all') + conversationItems(clientMode) + '</section>' +
      '<section class="chat"><header class="chat-head">' + A(clientMode?12:47) + '<div class="list-main"><strong>' + (clientMode?'Plomberie Express':'Sophie Martin') + '</strong><p>' + (clientMode?'En ligne · 4,8 ★★★★★':'Rénovation salle de bain · En ligne') + '</p></div><button class="icon-btn">' + I('phone','icon-sm') + '</button><button class="icon-btn">' + I('camera','icon-sm') + '</button><button class="icon-btn">' + I('more','icon-sm') + '</button></header><div class="chat-messages" data-chat-messages>' + messageBubbles(clientMode) + '</div><form class="composer" data-composer><input name="message" autocomplete="off" placeholder="Écrire un message..."><button class="btn btn-primary" type="submit">' + I('send') + '</button></form></section>' +
      '<aside class="conversation-detail"><h3>' + (clientMode?'Demande liée':'Détails de la conversation') + '</h3><section class="card card-pad mt-14"><div class="list-row">' + A(clientMode?12:47) + '<div class="list-main"><strong>' + (clientMode?'Plomberie Express':'Sophie Martin') + '</strong><p>' + (clientMode?'Prestataire vérifié':'Client depuis mai 2024') + '</p></div></div>' + Btn(clientMode?'Voir le profil':'Voir le profil','btn-block','','data-toast="Profil ouvert"') + '</section><section class="card card-pad"><h3>Informations</h3>' + detail('mail',clientMode?'contact@plomberie-express.fr':'sophie.martin@email.fr') + detail('phone','06 12 34 56 78') + detail('pin','15 rue des Fleurs, 69003 Lyon') + '</section><section class="card card-pad"><h3>' + (clientMode?'Options de contact':'Dernière demande') + '</h3><p>Rénovation salle de bain</p>' + B('En cours','orange') + '</section>' + (clientMode?'':'<section class="card card-pad">'+Btn('Créer un devis','btn-block','file','data-toast="Nouveau devis créé"')+Btn('Voir la demande','btn-block mt-14','inbox','data-toast="Demande ouverte"')+Btn('Bloquer le client','btn-danger btn-block mt-14','x','data-toast="Client bloqué"')+'</section>') + '</aside></div>';
  }

  function detail(iconName,text) {
    return '<div class="list-row"><span class="text-blue">' + I(iconName) + '</span><div class="list-main"><strong>' + text + '</strong></div></div>';
  }

  P.clientConversations = function () {
    var content = U.pageHead('Mes conversations','Retrouvez tous vos échanges avec les prestataires.') + messagesLayout(true) +
      '<section class="card card-pad mt-14"><h3>Autres moyens de communication</h3><div class="grid cols-4 mt-14">' + detail('phone','Téléphone · 06 12 34 56 78') + detail('mail','Email · contact@plomberie-express.fr') + detail('message','WhatsApp · Discuter maintenant') + '<div class="info-note">' + I('help') + 'Plus vous donnez de détails, plus les devis seront précis.</div></div></section>';
    return U.clientShell(content,{active:'clientConversations',darkTop:true});
  };

  function servicesTable() {
    var rows = [
      ['tableau','Installation prise extérieure','Installation de prise électrique extérieure étanche.','120 €','18','Active'],
      ['electric','Dépannage tableau électrique','Diagnostic et réparation de panneau électrique.','80 €','14','Active'],
      ['lights','Installation luminaires','Installation de tous types de luminaires intérieurs.','60 €','9','Active'],
      ['norms','Mise aux normes','Mise en conformité de votre installation électrique.','150 €','7','Active'],
      ['short','Dépannage court-circuit','Recherche et réparation de courts-circuits.','90 €','5','En pause'],
      ['plug','Ajout prises électriques','Ajout de prises électriques supplémentaires.','50 €','3','Active'],
      ['reno','Rénovation installation','Rénovation complète de votre installation électrique.','Sur devis','2','Brouillon']
    ];
    return '<div class="table-wrap"><table class="data-table"><thead><tr><th>Prestation</th><th>Catégorie</th><th>Tarif de base</th><th>Réservations</th><th>Statut</th><th>Actions</th></tr></thead><tbody>' + rows.map(function(r){
      return '<tr><td><div class="table-avatar"><span class="photo" style="width:50px;aspect-ratio:1.25">' + U.photo(r[0]) + '</span><div><strong>'+r[1]+'</strong><small>'+r[2]+'</small></div></div></td><td>'+B('Électricité','blue')+'</td><td><strong>'+r[3]+'</strong><small>À partir de</small></td><td>'+r[4]+'</td><td>'+B(r[5],r[5]==='Active'?'green':r[5]==='En pause'?'orange':'')+'</td><td><div class="table-actions"><button class="icon-btn">'+I('edit','icon-sm')+'</button><button class="icon-btn">'+I(r[5]==='Brouillon'?'chevron':'pause','icon-sm')+'</button><button class="icon-btn">'+I('more','icon-sm')+'</button></div></td></tr>';
    }).join('') + '</tbody></table></div>';
  }

  P.servicesList = function () {
    var content = U.pageHead('Mes prestations','Gérez vos services, tarifs et disponibilités.',Btn('Ajouter une prestation','btn-primary','plus','data-toast="Nouvelle prestation créée"')) +
      U.metrics([
        {title:'Prestations actives',value:'7',note:'+1 vs mois dernier',icon:'briefcase',trend:true},
        {title:'Vues totales',value:'342',note:'+18%',icon:'eye',trend:true},
        {title:'Réservations',value:'58',note:'+22%',icon:'calendar',color:'orange',trend:true},
        {title:'Chiffre d’affaires',value:'2 450 €',note:'+15%',icon:'wallet',color:'violet',trend:true},
        {title:'Note moyenne',value:'4,8 / 5',note:'Sur 142 avis',icon:'star',color:'violet'}
      ]) + U.tabs([['all','Toutes mes prestations'],['active','Actives'],['pause','En pause'],['draft','Brouillons'],['deleted','Supprimées']],'all') +
      '<div class="grid layout-3-2"><section class="card">' + U.filters('Rechercher une prestation...',['Toutes les catégories','Statut : Toutes']) + servicesTable() + '</section><aside><section class="card card-pad"><div class="page-head"><h3>Catégories</h3><a class="text-blue fw-700" href="#">Gérer</a></div>' + categoryLine('Électricité','7 prestations','#075bff') + categoryLine('Dépannage','3 prestations','#0aa565') + categoryLine('Installation','2 prestations','#ff9d00') + categoryLine('Mise aux normes','1 prestation','#7b35ef') + Btn('Ajouter une catégorie','btn-block mt-14','plus','data-toast="Nouvelle catégorie ajoutée"') + '</section><section class="card card-pad"><h3>Statistiques détaillées</h3>' + U.lineChart({two:'0,150 45,175 90,145 135,160 180,130 225,150 270,120 315,145 360,110 405,130 450,90 495,125 540,105 585,135 630,100 675,120 720,75 765,105 810,70'}) + '</section></aside></div><div class="info-note mt-14">' + I('help','icon-lg') + '<div><strong>Conseil : Optimisez vos prestations</strong><br>Des descriptions claires, des photos de qualité et des tarifs compétitifs attirent plus de clients.</div></div>';
    return U.providerShell(content,{active:'servicesDashboard',context:'Mes prestations',wide:true});
  };

  function categoryLine(name,count,color) {
    return '<div class="detail-line"><span><i class="legend-dot" style="background:'+color+'"></i>'+name+'</span><span class="text-muted">'+count+'</span></div>';
  }

  P.servicesDashboard = function () {
    var serviceList = [
      ['Dépannage électrique','Interventions rapides pour pannes et dysfonctionnements.','À partir de 60 €','orange','briefcase'],
      ['Installation prise / Interrupteur','Installation et remplacement de prises.','À partir de 40 €','blue','wrench'],
      ['Installation luminaires','Pose de luminaires intérieurs et extérieurs.','À partir de 70 €','violet','star'],
      ['Mise aux normes','Mise en conformité électrique.','À partir de 150 €','green','shield'],
      ['Ajout prises électriques','Ajout de prises supplémentaires.','À partir de 50 €','orange','settings'],
      ['Rénovation installation','Rénovation complète de votre installation.','Sur devis','red','settings']
    ];
    var content = U.pageHead('Mes prestations','Gérez vos services, tarifs et disponibilités.') +
      U.metrics([
        {title:'Services actifs',value:'6',note:'+1 ce mois',icon:'briefcase',trend:true},
        {title:'Prestations réalisées',value:'24',note:'+8%',icon:'check',color:'green',trend:true},
        {title:'Taux d’acceptation',value:'92%',note:'+5%',icon:'chart',color:'orange',trend:true},
        {title:'Note moyenne',value:'4,8 / 5',note:'★★★★★',icon:'star',color:'violet'},
        {title:'Revenus ce mois',value:'2 450 €',note:'+15%',icon:'wallet',color:'cyan',trend:true}
      ]) + U.tabs([['services','Mes services'],['rates','Tarifs & devis'],['availability','Disponibilités'],['zones','Zones d’intervention'],['portfolio','Réalisations'],['docs','Documents']],'services') +
      '<div class="grid cols-3"><section class="card"><div class="card-head"><div><h3>Mes services</h3><p class="card-subtitle">Gérez vos services proposés aux clients.</p></div>'+Btn('Ajouter un service','btn-primary btn-sm','plus','data-toast="Service ajouté"')+'</div><div class="section-list">'+serviceList.map(function(r){return '<div class="list-row"><span class="list-icon" style="color:var(--'+r[3]+')">'+I(r[4])+'</span><div class="list-main"><strong>'+r[0]+'</strong><p>'+r[1]+'</p></div><strong>'+r[2]+'</strong>'+S(r[0]!=='Rénovation installation')+'</div>';}).join('')+'</div></section>' +
      '<section class="card"><div class="card-head"><h3>Aperçu des prestations</h3>'+B('30 derniers jours','blue')+'</div>'+U.lineChart({two:'0,185 45,170 90,150 135,135 180,155 225,145 270,152 315,115 360,140 405,110 450,128 495,95 540,125 585,98 630,117 675,75 720,98 765,50 810,70'})+'<div class="grid cols-2 card-pad"><article class="metric"><span class="metric-icon">'+I('calendar')+'</span><div><strong>24</strong><small>Prestations réalisées</small><p class="trend-up">+8%</p></div></article><article class="metric cyan"><span class="metric-icon">'+I('wallet')+'</span><div><strong>2 450 €</strong><small>Montant généré</small><p class="trend-up">+15%</p></div></article></div></section>' +
      '<aside><section class="card card-pad"><div class="page-head"><h3>Prochaines prestations</h3><a class="text-blue" href="#">Voir le calendrier</a></div>'+nextJob('Aujourd’hui · 14:00','Dépannage électrique','Villeurbanne 69100','green')+nextJob('Demain · 09:00','Installation prise extérieure','Lyon 69003','green')+nextJob('Mer. 15 mai · 11:30','Mise aux normes','Bron 69500','orange')+nextJob('Ven. 17 mai · 16:00','Installation luminaires','Lyon 69007','green')+'</section><section class="card card-pad"><h3>Performance de mes prestations</h3><div style="display:flex;align-items:center;gap:25px;margin-top:20px">'+U.donut('92%','Taux d’acceptation')+'<div>'+categoryLine('Acceptées','22 (92%)','#0aa565')+categoryLine('En attente','2 (8%)','#ff9d00')+categoryLine('Refusées','0 (0%)','#ff3d4f')+'</div></div></section></aside></div>' +
      '<div class="grid cols-2 mt-14"><section class="card card-pad"><div class="page-head"><h3>Zones d’intervention</h3>'+B('3 zones actives','blue')+'</div>'+U.mapBox('polygon')+'</section><section class="card card-pad"><h3>Avis récents</h3>'+reviewMini(47,'Sophie Martin','Intervention rapide et professionnelle.')+reviewMini(13,'Thomas Bernard','Travail soigné, très bon contact.')+reviewMini(45,'Pauline Leroy','Très bon prestataire, efficace.')+'</section></div>';
    return U.providerShell(content,{active:'servicesDashboard',context:'Mes prestations',wide:true});
  };

  function nextJob(date,title,city,color) {
    return '<div class="list-row"><span class="list-icon">'+I('briefcase')+'</span><div class="list-main"><small>'+date+'</small><strong>'+title+'</strong><p>'+city+'</p></div>'+B(color==='green'?'Confirmé':'En attente',color)+'</div>';
  }
  function reviewMini(id,name,text) {
    return '<div class="list-row">'+A(id,'avatar-sm')+'<div class="list-main"><strong>'+name+'</strong><p>'+text+'</p></div><span class="stars">★★★★★</span></div>';
  }

  P.agencyInfo = function () {
    var content = U.pageHead('Paramètres','Gérez les informations et les détails de votre agence.') +
      U.tabs([['dashboard','Tableau de bord'],['ads','Mes annonces'],['requests','Demandes reçues'],['visits','Visites programmées'],['messages','Messages'],['favorites','Favoris'],['stats','Statistiques'],['agency','Informations agence']],'agency') +
      '<div class="grid cols-3"><div><section class="card card-pad"><h3>Informations générales</h3><p class="card-subtitle">Modifiez les informations principales de votre agence.</p><div class="form-grid mt-14">'+field('Nom de l’agence','Immo Plus',true)+field('Slogan','Votre projet immobilier, notre priorité',true)+field('Type d’agence','Agence immobilière')+field('Année de création','2015')+field('SIRET','812 345 678 00023')+field('TVA intracommunautaire','FR81 812345678')+area('À propos de l’agence','Immo Plus est une agence immobilière indépendante spécialisée dans la transaction, la location et la gestion de biens immobiliers.',true)+'</div>'+Btn('Enregistrer les modifications','btn-primary mt-14','','data-toast="Informations enregistrées"')+'</section><section class="card card-pad"><h3>Horaires d’ouverture</h3>'+['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'].map(function(d,n){return '<div class="detail-line"><strong>'+d+'</strong><span>'+ (n<5?'09:00 — 12:30 &nbsp; 14:00 — 18:30':'Fermé') +'</span></div>';}).join('')+'</section></div>' +
      '<div><section class="card card-pad"><h3>Logo et visuels</h3><div class="grid cols-2 mt-14"><div class="upload-zone">'+I('building','icon-lg')+'<strong>IMMO PLUS</strong></div><div class="photo">'+U.photo('agency-cover')+'</div></div></section><section class="card card-pad"><h3>Nos services</h3><div class="grid cols-4 mt-14">'+['Achat / Vente','Location','Gestion locative','Estimation','Conseil immobilier','Viager','Neuf','Investissement locatif'].map(function(x){return check(x);}).join('')+'</div></section><section class="card card-pad"><h3>Zones d’intervention principales</h3><p>'+B('Lyon','blue')+' '+B('Villeurbanne','blue')+' '+B('Bron','blue')+' '+B('Écully','blue')+'</p>'+U.mapBox('polygon')+'</section></div>' +
      '<aside><section class="card card-pad"><h3>Coordonnées</h3><div class="form-grid mt-14">'+field('Adresse','123 Rue de la République, 69002 Lyon, France',true)+field('Téléphone','04 78 123 456',true)+field('Email','contact@immoplus.fr',true)+field('Site web','https://www.immoplus.fr',true)+'</div><h3 style="margin-top:18px">Réseaux sociaux</h3>'+detail('message','Facebook · immoplus')+detail('image','Instagram · immoplus')+detail('building','LinkedIn · immoplus')+'</section><section class="card card-pad"><h3>Documents de l’agence</h3>'+doc('Carte professionnelle','carte_pro_immoplus.pdf')+doc('Assurance RCP','rcp_immoplus.pdf')+doc('Extrait Kbis','kbis_immoplus.pdf')+Btn('Ajouter un document','btn-block mt-14','plus','data-toast="Sélecteur de document ouvert"')+'</section></aside></div>';
    return U.providerShell(content,{active:'companyInfo',context:'Paramètres',wide:true});
  };

  function field(label,value,full) { return '<div class="form-group '+(full?'full':'')+'"><label>'+label+'</label><input value="'+value+'"></div>'; }
  function area(label,value,full) { return '<div class="form-group '+(full?'full':'')+'"><label>'+label+'</label><textarea>'+value+'</textarea></div>'; }
  function check(text) { return '<label class="check-row"><input type="checkbox" checked><strong>'+text+'</strong></label>'; }
  function doc(name,file) { return '<div class="list-row"><span class="list-icon">'+I('file')+'</span><div class="list-main"><strong>'+name+'</strong><p>'+file+'</p></div>'+I('download','icon-sm')+'</div>'; }

  P.notifications = function () {
    var types = [
      ['Nouvelles demandes','Notifications lorsque vous recevez une nouvelle demande de devis.','inbox',true],
      ['Messages','Notifications pour les nouveaux messages de vos clients.','message',true],
      ['Réservations et rendez-vous','Notifications pour les confirmations et rappels.','calendar',true],
      ['Avis et évaluations','Notifications lorsque vous recevez un nouvel avis.','star',true],
      ['Paiements et revenus','Notifications concernant vos paiements et factures.','wallet',true],
      ['Promotions et conseils','Conseils, nouveautés et offres pour développer votre activité.','gift',false],
      ['Actualités de TrouveMoi','Nouveautés, fonctionnalités et informations importantes.','pin',false]
    ];
    var content = U.pageHead('Notifications','Choisissez les notifications que vous souhaitez recevoir et comment vous souhaitez les recevoir.') + U.settingsTabs('notifications',true) +
      '<div class="grid cols-2"><section class="card card-pad"><h3>Types de notifications</h3><p class="card-subtitle">Activez ou désactivez les notifications que vous souhaitez recevoir.</p><div class="section-list" style="padding:0">'+types.map(function(r){return '<div class="list-row"><span class="list-icon">'+I(r[2])+'</span><div class="list-main"><strong>'+r[0]+'</strong><p>'+r[1]+'</p></div>'+S(r[3])+' '+I('down','icon-sm')+'</div>';}).join('')+'</div><div class="info-note">'+I('help')+'Vous pouvez recevoir des notifications importantes même si elles sont désactivées.</div></section>' +
      '<div><section class="card card-pad"><h3>Canaux de réception</h3><p class="card-subtitle">Choisissez comment vous souhaitez recevoir vos notifications.</p>'+channel('bell','Notifications in-app','Recevoir les notifications directement dans votre espace prestataire.')+channel('mail','Email','jean.dupont@email.com')+channel('phone','SMS','06 12 34 56 78')+'</section><section class="card card-pad"><div class="page-head"><h3>Heures de silence</h3>'+S(true)+'</div><p class="card-subtitle">Définissez les périodes pendant lesquelles vous ne souhaitez pas recevoir de notifications.</p><div class="form-grid mt-14">'+field('Du','22:00')+field('Au','07:00')+'</div><div class="tabs" style="margin-top:14px">'+['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'].map(function(d,n){return '<button class="tab '+(n>4?'active':'')+'">'+d+'</button>';}).join('')+'</div><div class="info-note">'+I('clock')+'Les notifications importantes seront toujours envoyées pendant les heures de silence.</div></section><div style="text-align:right;margin-top:18px">'+Btn('Enregistrer les modifications','btn-primary','','data-toast="Notifications enregistrées"')+'</div></div></div>';
    return U.providerShell(content,{active:'settings',context:'Paramètres'});
  };

  function channel(iconName,title,text) {
    return '<div class="list-row card" style="margin-top:10px;padding:12px"><span class="list-icon">'+I(iconName)+'</span><div class="list-main"><strong>'+title+'</strong><p>'+text+'</p></div><input type="checkbox" checked style="accent-color:var(--blue)"></div>';
  }

  P.dashboard = function () {
    var content = U.pageHead('Tableau de bord','Bienvenue Plomberie Express, voici ce qui se passe aujourd’hui.',Btn('Nouvelle annonce','btn-primary','plus','data-toast="Nouvelle annonce créée"')) +
      U.metrics([
        {title:'Nouvelles demandes',value:'12',note:'+3 depuis hier',icon:'inbox',trend:true},
        {title:'Devis en attente',value:'8',note:'+2 depuis hier',icon:'file',color:'green',trend:true},
        {title:'Chantiers en cours',value:'5',note:'Stable',icon:'wrench',color:'violet'},
        {title:'Note moyenne',value:'4,8 /5',note:'56 avis clients',icon:'star',color:'orange'},
        {title:'Vues de votre profil',value:'248',note:'+32 cette semaine',icon:'eye',trend:true}
      ]) +
      '<div class="grid cols-3"><section class="card card-pad"><div class="page-head"><h3>Demandes récentes</h3><a class="text-blue" href="'+F.requests+'">Voir toutes</a></div>'+recentRequest('Rénovation salle de bain','Lyon 3e','NOUVELLE','orange')+recentRequest('Fuite d’eau + dépannage','Villeurbanne','NOUVELLE','orange')+recentRequest('Installation chauffe-eau','Lyon 7e','RÉPONDUE','green')+recentRequest('Remplacement robinetterie','Lyon 6e','RÉPONDUE','green')+recentRequest('Débouchage canalisation','Lyon 8e','RÉPONDUE','green')+'</section>' +
      '<section class="card card-pad"><div class="page-head"><h3>Mon agenda</h3><a class="text-blue" href="#">Voir tout</a></div><div class="calendar-grid">'+['Lun','Mar','Mer','Jeu','Ven','Sam','Dim','29','30','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24','25','26','27','28','29','30','31','1','2'].map(function(d,n){return '<span class="'+(d==='16'?'today':n<9?'muted':'')+'">'+d+'</span>';}).join('')+'</div><div class="separator"></div><strong>Aujourd’hui · Jeudi 16 mai</strong><p><span class="text-blue">14:00</span> &nbsp; Rendez-vous chantier</p></section>' +
      '<section class="card card-pad"><h3>Mes tâches</h3>'+checkTask('Répondre à 3 nouvelles demandes','Priorité haute')+checkTask('Établir devis - Fuite d’eau','Aujourd’hui')+checkTask('Commander matériel','Demain')+checkTask('Suivi chantier - Lyon 7e','17/05/2024')+'<div class="info-note">'+I('help')+'<div><strong>Conseil du jour</strong><br>Complétez vos réalisations avec des photos.</div></div></section></div>' +
      '<div class="grid cols-3 mt-14"><section class="card"><div class="card-head"><h3>Performance de mes demandes</h3>'+B('7 derniers jours','blue')+'</div>'+U.lineChart({two:'0,165 45,150 90,135 135,150 180,127 225,145 270,130 315,118 360,140 405,126 450,145 495,133 540,118 585,136 630,127 675,142 720,130 765,120 810,134'})+'</section><section class="card card-pad"><h3>Messages récents</h3>'+reviewMini(47,'Sophie Martin','Bonjour, j’aimerais avoir un devis...')+reviewMini(13,'Michel Dupont','Merci pour votre retour...')+reviewMini(45,'Laura Bernard','Pouvez-vous intervenir plus tôt ?')+reviewMini(12,'Julien Moreau','D’accord, merci beaucoup.')+'</section><section class="card card-pad"><h3>Avis clients récents</h3>'+reviewMini(15,'Thomas Legrand','Intervention rapide et efficace.')+reviewMini(32,'Camille Petit','Travail soigné, ponctuel et à l’écoute.')+reviewMini(11,'Antoine Dubois','Excellent service, tarif juste.')+'</section></div>';
    return U.providerShell(content,{active:'dashboard',includeSettings:true,darkTop:true,wide:true});
  };

  function recentRequest(title,city,status,color) {
    return '<div class="list-row"><div class="list-main"><strong>'+title+'</strong><p>'+city+'</p></div>'+B(status,color)+I('chevron','icon-sm')+'</div>';
  }
  function checkTask(title,date) {
    return '<label class="list-row"><input type="checkbox" style="accent-color:var(--blue)"><div class="list-main"><strong>'+title+'</strong><p>'+date+'</p></div>'+I('chevron','icon-sm')+'</label>';
  }
  function reviewMini(id,name,text) {
    return '<div class="list-row">'+A(id,'avatar-sm')+'<div class="list-main"><strong>'+name+'</strong><p>'+text+'</p></div><span class="stars">★★★★★</span></div>';
  }

  P.requests = function () {
    var content = U.pageHead('Mes demandes','Toutes les demandes reçues de vos clients.',Btn('Exporter','','download','data-toast="Export des demandes généré"')) +
      U.metrics([
        {title:'Total demandes',value:'12',note:'+3 depuis hier',icon:'inbox',trend:true},
        {title:'En attente',value:'5',note:'Priorité à traiter',icon:'clock',color:'orange'},
        {title:'Répondues',value:'4',note:'En attente de devis',icon:'message',color:'green'},
        {title:'Devis envoyés',value:'3',note:'En attente réponse',icon:'file',color:'violet'},
        {title:'Acceptées',value:'2',note:'Chantiers à venir',icon:'check'},
        {title:'Refusées',value:'1',note:'Cette semaine',icon:'x',color:'red'}
      ],'metrics-6') + '<div class="grid" style="grid-template-columns:minmax(0,1fr) 290px"><section class="card"><div class="card-head no-line">'+U.tabs([['all','Toutes (12)'],['pending','En attente (5)'],['answered','Répondues (4)'],['quotes','Devis envoyés (3)'],['accepted','Acceptées (2)'],['refused','Refusées (1)']],'all')+'</div>'+U.filters('Rechercher une demande...',['Toutes catégories','Toutes zones','Période'])+requestTable()+'</section>'+requestInspector()+'</div>';
    return U.providerShell(content,{active:'requests',includeSettings:true,darkTop:true,wide:true});
  };

  function requestTable() {
    var rows = [
      ['Rénovation salle de bain','Remplacement baignoire et carrelage',47,'Sophie Martin','Plomberie','Lyon 3e','En attente'],
      ['Installation chauffe-eau','Remplacer ancien chauffe-eau 200L',13,'Michel Dupont','Chauffe-eau','Villeurbanne','Répondue'],
      ['Fuite d’eau + dépannage','Fuite sous évier cuisine',45,'Laura Bernard','Dépannage','Lyon 7e','Devis envoyé'],
      ['Remplacement radiateur','Remplacer 2 radiateurs',15,'Thomas Legrand','Chauffage','Lyon 6e','Acceptée'],
      ['Réparation chasse d’eau','Chasse d’eau qui fuit',32,'Camille Petit','Sanitaire','Bron','En attente'],
      ['Installation robinetterie','Robinet thermostatique douche',12,'Julien Moreau','Plomberie','Caluire-et-Cuire','En attente'],
      ['Débouchage canalisation','Canalisation évier bouchée',11,'Antoine Dubois','Dépannage','Lyon 8e','Refusée']
    ];
    return '<div class="table-wrap"><table class="data-table"><thead><tr><th>Demande</th><th>Client</th><th>Catégorie</th><th>Localisation</th><th>Reçue le</th><th>Statut</th><th>Actions</th></tr></thead><tbody>'+rows.map(function(r,n){var color=r[6]==='Répondue'||r[6]==='Acceptée'?'green':r[6]==='En attente'?'orange':r[6]==='Refusée'?'red':'violet';return '<tr class="'+(n===0?'selected':'')+'"><td><strong>'+r[0]+'</strong><small>'+r[1]+'</small></td><td><div class="table-avatar">'+A(r[2],'avatar-sm')+'<div><strong>'+r[3]+'</strong><small>★ 4,9</small></div></div></td><td><span class="text-blue">'+r[4]+'</span></td><td><strong>'+r[5]+'</strong><small>69003</small></td><td>'+(n===0?'Aujourd’hui':'Hier')+'<small>10:30</small></td><td>'+B(r[6],color)+'</td><td><div class="table-actions"><button class="icon-btn">'+I('eye','icon-sm')+'</button><button class="icon-btn">'+I('message','icon-sm')+'</button><button class="icon-btn">'+I('more','icon-sm')+'</button></div></td></tr>';}).join('')+'</tbody></table></div>';
  }
  function requestInspector() {
    return '<aside class="inspector"><section class="card card-pad">'+B('EN ATTENTE','orange')+'<h2 style="margin:10px 0">Rénovation salle de bain</h2><p>Remplacement baignoire et carrelage</p><div class="list-row">'+A(47)+'<div class="list-main"><strong>Sophie Martin</strong><p>06 12 34 56 78<br>sophie.martin@email.fr</p></div></div><h3>Détails de la demande</h3><p>✓ Remplacement de la baignoire par une douche</p><p>✓ Changement du carrelage mural et sol</p><p>✓ Budget : entre 2 000 € et 3 000 €</p><p>✓ Besoin dans les 3 prochaines semaines</p><h3 style="margin-top:18px">Localisation</h3><p><strong>Lyon 3e (69003)</strong></p><div style="min-height:180px">'+U.mapBox('polygon')+'</div><h3 style="margin-top:18px">Actions rapides</h3>'+Btn('Répondre au client','btn-primary btn-block','message','data-toast="Conversation ouverte"')+Btn('Créer un devis','btn-success btn-block mt-14','file','data-toast="Nouveau devis créé"')+Btn('Refuser la demande','btn-danger btn-block mt-14','x','data-toast="Demande refusée"')+'</section></aside>';
  }

  P.projects = function () {
    var content = U.pageHead('Mes chantiers','Suivez l’avancement de tous vos chantiers en cours et terminés.',Btn('Exporter','','download','data-toast="Export des chantiers généré"')) +
      U.metrics([
        {title:'Total chantiers',value:'14',note:'+3 ce mois',icon:'file',trend:true},
        {title:'En cours',value:'6',note:'42%',icon:'clock',color:'orange'},
        {title:'Terminés',value:'4',note:'28%',icon:'check',color:'green'},
        {title:'En attente',value:'2',note:'14%',icon:'file',color:'violet'},
        {title:'Annulés',value:'2',note:'14%',icon:'x'}
      ]) + '<div class="grid" style="grid-template-columns:minmax(0,1fr) 300px"><section class="card"><div class="card-head no-line">'+U.tabs([['all','Tous (14)'],['running','En cours (6)'],['done','Terminés (4)'],['pending','En attente (2)'],['cancelled','Annulés (2)']],'all')+'</div>'+U.filters('Rechercher un chantier...',['Toutes catégories','Tous statuts','Toutes zones'])+projectTable()+'</section>'+projectInspector()+'</div>';
    return U.providerShell(content,{active:'projects',includeSettings:true,darkTop:true,wide:true});
  };

  function projectTable() {
    var rows = [
      ['bath','Rénovation salle de bain',47,'Sophie Martin','Plomberie','Lyon 3e','En cours',65,'16/05/2024'],
      ['heat','Installation chauffage',13,'Michel Dupont','Chauffage','Villeurbanne','En cours',40,'15/05/2024'],
      ['leak','Fuite d’eau + dépannage',45,'Laura Bernard','Plomberie','Lyon 7e','Terminé',100,'10/05/2024'],
      ['radiator','Remplacement radiateur',15,'Thomas Legrand','Chauffage','Lyon 6e','En attente',0,'20/05/2024'],
      ['bath2','Création salle de bain',32,'Camille Petit','Plomberie','Bron','En cours',30,'18/05/2024'],
      ['drain','Débouchage canalisation',11,'Antoine Dubois','Plomberie','Lyon 8e','Terminé',100,'08/05/2024'],
      ['tap','Réparation robinetterie',47,'Nathalie Blanc','Plomberie','Sainte-Foy-lès-Lyon','Annulé',0,'04/05/2024']
    ];
    return '<div class="table-wrap"><table class="data-table"><thead><tr><th>Chantier</th><th>Client</th><th>Catégorie</th><th>Localisation</th><th>Statut</th><th>Avancement</th><th>Date de début</th><th>Actions</th></tr></thead><tbody>'+rows.map(function(r,n){var color=r[6]==='Terminé'?'green':r[6]==='En cours'?'orange':r[6]==='Annulé'?'red':'orange';return '<tr class="'+(n===0?'selected':'')+'"><td><div class="table-avatar"><span class="photo" style="width:55px;aspect-ratio:1.3">'+U.photo(r[0])+'</span><div><strong>'+r[1]+'</strong><small>Réf. CH-2024-01'+(5-n)+'</small></div></div></td><td><div class="table-avatar">'+A(r[2],'avatar-sm')+'<div><strong>'+r[3]+'</strong><small>'+r[5]+'</small></div></div></td><td><span class="text-blue">'+r[4]+'</span></td><td><strong>'+r[5]+'</strong><small>69003</small></td><td>'+B(r[6],color)+'</td><td><strong>'+r[7]+'%</strong><div class="progress '+(r[7]===100?'green':'orange')+'"><span style="width:'+r[7]+'%"></span></div></td><td>'+r[8]+'</td><td><div class="table-actions"><button class="icon-btn">'+I('eye','icon-sm')+'</button><button class="icon-btn">'+I('more','icon-sm')+'</button></div></td></tr>';}).join('')+'</tbody></table></div>';
  }
  function projectInspector() {
    return '<aside class="inspector"><section class="card p-0"><div class="photo" style="height:150px;border-radius:10px 10px 0 0">'+U.photo('bathroom-project')+'</div><div class="card-pad">'+B('EN COURS','orange')+'<h2 style="margin:10px 0 2px">Rénovation salle de bain</h2><small>Réf. CH-2024-015</small><div class="separator"></div><h3>Client</h3><div class="list-row">'+A(47)+'<div class="list-main"><strong>Sophie Martin</strong><p>06 12 34 56 78<br>sophie.martin@email.fr</p></div></div>'+U.detailLine('Date de début','16/05/2024')+U.detailLine('Fin prévue','30/05/2024')+U.detailLine('Montant devis','2 850,00 € TTC')+U.detailLine('Montant facturé','1 850,00 € TTC')+'<h3 style="margin-top:17px">Avancement du chantier &nbsp; 65%</h3><div class="progress orange"><span style="width:65%"></span></div><p class="text-green">● Démontage ancien équipement</p><p class="text-green">● Plomberie et évacuations</p><p class="text-green">● Carrelage et murs</p><p class="text-orange">○ Installation équipement</p>'+Btn('Voir le détail du chantier','btn-primary btn-block mt-14','','data-toast="Détail du chantier ouvert"')+Btn('Contacter le client','btn-block mt-14','message','data-toast="Conversation ouverte"')+'</div></section></aside>';
  }
})();

(function () {
  'use strict';
  var U = window.TrouveMoiUI;
  var F = U.FILES;
  var I = U.icon;
  var A = U.avatar;
  var B = U.badge;
  var S = U.switcher;
  var Btn = U.button;
  var P = window.TrouveMoiPages = window.TrouveMoiPages || {};

  function formField(label, value, type, full) {
    var control = type === 'textarea'
      ? '<textarea>' + value + '</textarea>'
      : type === 'select'
        ? '<select><option>' + value + '</option></select>'
        : '<input type="' + (type || 'text') + '" value="' + value + '">';
    return '<div class="form-group ' + (full ? 'full' : '') + '"><label>' + label + '</label>' + control + '</div>';
  }

  function checkRow(title, text, checked) {
    return '<label class="check-row"><input type="checkbox" ' + (checked ? 'checked' : '') + '><span><strong>' + title + '</strong>' + (text ? '<small>' + text + '</small>' : '') + '</span></label>';
  }

  function serviceRows() {
    var rows = [
      ['Installation électrique', 'Installation complète de systèmes électriques neufs ou en rénovation.', '80 €', '2 à 4 heures', 'briefcase'],
      ['Dépannage électrique', 'Intervention rapide pour tous types de pannes électriques.', '60 €', '1 à 2 heures', 'wrench'],
      ['Mise aux normes', 'Mise en conformité de votre installation électrique.', '150 €', '½ journée', 'shield'],
      ['Éclairage intérieur & extérieur', 'Installation et remplacement de luminaires.', '40 €', '1 à 3 heures', 'star'],
      ['Installation de prises et interrupteurs', 'Ajout ou remplacement de prises et points d’alimentation.', '30 €', '1 heure', 'settings']
    ];
    return rows.map(function (r) {
      return '<div class="list-row"><span class="list-icon">' + I(r[4], 'icon-lg') + '</span><div class="list-main"><strong>' + r[0] + '</strong><p>' + r[1] + '</p></div><div class="list-meta">Catégorie<strong>Électricité</strong></div><div class="list-meta">Prix à partir de<strong>' + r[2] + '</strong></div><div class="list-meta">Durée moyenne<strong>' + r[3] + '</strong></div>' + S(true) + Btn('Modifier', 'btn-sm', 'edit', 'data-toast="Service prêt à être modifié"') + '<button class="icon-btn">' + I('more', 'icon-sm') + '</button></div>';
    }).join('');
  }

  function statusLegend() {
    return '<div class="legend"><span><i class="legend-dot"></i>Dépannage électrique 45%</span><span><i class="legend-dot" style="background:#0aa565"></i>Installation 30%</span><span><i class="legend-dot" style="background:#ff9d00"></i>Mise aux normes 15%</span><span><i class="legend-dot" style="background:#7b35ef"></i>Autres 10%</span></div>';
  }

  P.clientSettings = function () {
    var sideSettings = ['Mon profil', 'Informations personnelles', 'Notifications', 'Sécurité', 'Moyens de paiement', 'Adresses', 'Abonnement', 'Confidentialité', 'Préférences d’affichage', 'Supprimer mon compte'];
    var content = U.pageHead('Paramètres', 'Gérez votre compte, vos préférences et la sécurité.') +
      '<div class="grid" style="grid-template-columns:230px minmax(430px,1.6fr) minmax(280px,1fr)">' +
        '<section class="card card-pad"><nav class="side-nav">' + sideSettings.map(function (x, n) { return '<a class="side-link ' + (n === 0 ? 'active' : '') + '" href="#">' + I(n === 0 ? 'user' : n === 2 ? 'bell' : n === 3 ? 'shield' : n === 4 ? 'wallet' : n === 5 ? 'pin' : 'settings') + x + '</a>'; }).join('') + '</nav></section>' +
        '<div><section class="card card-pad"><div class="page-head"><div><h3>Mon profil</h3><p>Gérez vos informations personnelles.</p></div>' + Btn('Modifier', '', 'edit', 'data-toast="Mode édition activé"') + '</div><div style="margin-bottom:17px">' + A(47, 'avatar-lg') + '</div><div class="form-grid">' +
          formField('Prénom', 'Sophie') + formField('Nom', 'Martin') + formField('Email', 'sophie.martin@email.com', 'email') + formField('Téléphone', '06 12 34 56 78') + formField('Date de naissance', '15/04/1990') + formField('Genre', 'Femme', 'select') + formField('Bio', 'Passionnée par la décoration d’intérieur et les projets de rénovation. J’aime faire appel à des professionnels de confiance.', 'textarea', true) +
        '</div></section><section class="card card-pad"><h3>Adresse principale</h3><p class="card-subtitle">Cette adresse sera utilisée pour vos demandes et réservations.</p><div class="form-grid cols-3 mt-14">' + formField('Adresse', '12 rue des Lilas', 'text', true) + formField('Code postal', '69003') + formField('Ville', 'Lyon') + formField('Pays', 'France') + '</div></section></div>' +
        '<div><section class="card card-pad"><h3>Statut du compte</h3><p class="card-subtitle">Votre compte est actif et vérifié.</p><div class="info-note success-note">' + I('shield') + '<div><strong>Compte vérifié</strong><br>Profitez de toutes les fonctionnalités.</div></div></section><section class="card card-pad"><h3>Photo de profil</h3><p class="card-subtitle">Votre photo est visible par les prestataires.</p><div class="list-row">' + A(47) + Btn('Changer la photo', '', '', 'data-toast="Sélecteur de photo ouvert"') + '</div><button class="btn btn-ghost text-red">Supprimer</button></section><section class="card card-pad"><h3>Préférences de communication</h3>' + checkRow('Par email', 'Recevoir les notifications par email', true) + checkRow('Par SMS', 'Recevoir les notifications par SMS', true) + checkRow('Par téléphone', 'Être contactée si nécessaire', false) + '<div class="info-note">' + I('help') + 'Vous pouvez modifier ces préférences à tout moment.</div></section></div>' +
      '</div><section class="card card-pad mt-14"><h3>Préférences de recherche</h3><p class="card-subtitle">Personnalisez votre expérience pour recevoir des recommandations pertinentes.</p><div class="grid cols-3 mt-14"><div><strong>Catégories préférées</strong><p>' + B('⚡ Électricité', 'blue') + ' ' + B('💧 Plomberie', 'blue') + ' ' + B('Peinture', 'blue') + '</p></div>' + formField('Zone d’intervention préférée', 'Lyon et ses alentours (20 km)', 'select') + formField('Budget moyen par projet', '500 € - 2 000 €', 'select') + '</div><div style="text-align:right">' + Btn('Enregistrer les modifications', 'btn-primary', '', 'data-toast="Modifications enregistrées"') + '</div></section>';
    return U.clientShell(content, { active: 'clientSettings', context: '' });
  };

  P.servicesSettings = function () {
    var content = U.pageHead('Services', 'Gérez les services que vous proposez à vos clients.') +
      U.settingsTabs('servicesSettings', true) +
      '<section class="card"><div class="card-head"><div><h3>Vos services</h3><p class="card-subtitle">Ajoutez, modifiez ou organisez les services que vous proposez.</p></div>' + Btn('Ajouter un service', 'btn-primary', 'plus', 'data-toast="Nouveau service ajouté"') + '</div><div class="section-list">' + serviceRows() + '</div><div class="center" style="padding:12px;color:var(--muted);font-size:10px">' + I('settings', 'icon-sm') + ' Glissez pour réorganiser vos services</div></section>' +
      '<section class="info-note mt-14" style="padding:18px">' + I('star', 'icon-lg') + '<div><strong>Mettez en avant vos services</strong><br>Les services mis en avant apparaissent en priorité sur votre profil et dans les résultats de recherche.</div><span style="margin-left:auto">' + Btn('En savoir plus', '', '', 'data-toast="Conseils ouverts"') + '</span></section>';
    return U.providerShell(content, { active: 'settings', context: 'Paramètres' });
  };

  P.quoteSettings = function () {
    var content = U.pageHead('Paramètres', 'Accueil  ›  Paramètres  ›  Devis et paiement') + U.settingsTabs('quoteSettings', false) +
      '<div class="grid cols-3"><div><section class="card card-pad"><h3>Devis</h3><p class="card-subtitle">Configurez vos préférences pour l’envoi et la gestion des devis.</p><div class="form-grid mt-14">' + formField('Validité des devis', '30 jours', 'select', true) + '</div>' + checkRow('Activer la signature électronique', 'Permettre à vos clients de signer vos devis en ligne.', true) + checkRow('Envoyer un rappel automatique', 'Relancer automatiquement les devis non acceptés.', true) + formField('Délai avant rappel', '3 jours après l’envoi', 'select') + '</section><section class="card card-pad"><h3>Paiement en ligne</h3><p class="card-subtitle">Activez les paiements par carte bancaire pour vos clients.</p><div class="detail-line"><strong>Activer le paiement en ligne</strong>' + S(true, 'green') + '</div><h3 style="margin-top:18px">Moyens de paiement acceptés</h3><div class="grid cols-2 mt-14">' + B('💳 Carte bancaire', 'green') + B('Google Pay', 'blue') + B('Apple Pay', 'blue') + B('Virement bancaire', 'blue') + '</div><div class="info-note">' + I('help') + 'Le paiement en ligne renforce la confiance et accélère les règlements.</div></section></div>' +
      '<div><section class="card card-pad" style="min-height:550px"><h3>Acompte et paiement</h3><p class="card-subtitle">Définissez vos conditions d’acompte et de paiement.</p><div class="detail-line"><strong>Demander un acompte</strong>' + S(true, 'green') + '</div><div class="form-grid">' + formField('Pourcentage d’acompte', '30 %', 'select', true) + formField('Paiement à la signature du devis', 'Acompte uniquement', 'select', true) + formField('Délai de paiement final', 'À la réception des travaux', 'select', true) + '</div><div class="card mt-14 card-pad"><strong>Conditions générales de vente</strong><p class="card-subtitle">Dernière mise à jour : 15/05/2024</p>' + Btn('Modifier', 'btn-sm', 'edit', 'data-toast="Conditions générales ouvertes"') + '</div></section></div>' +
      '<div><section class="card card-pad"><h3>Préférences d’affichage des prix</h3><p class="card-subtitle">Choisissez comment vous affichez vos prix.</p>' + checkRow('Afficher les prix', '', true) + checkRow('Masquer les prix', '', false) + '<div class="form-grid">' + formField('Type d’affichage', 'À partir de', 'select', true) + formField('Devise', 'Euro (€)', 'select', true) + '</div></section><section class="card card-pad"><h3>Modèles de devis</h3><p class="card-subtitle">Personnalisez vos modèles de devis.</p><div class="list-row"><span class="list-icon">' + I('file') + '</span><div class="list-main"><strong>Modèle moderne</strong><p>Modèle actuel · Par défaut</p></div></div>' + Btn('Personnaliser le modèle', '', 'edit', 'data-toast="Éditeur de modèle ouvert"') + '</section><section class="card card-pad"><h3>Facturation automatique</h3>' + checkRow('Créer la facture automatiquement', '', true) + checkRow('Envoyer la facture automatiquement', '', true) + formField('Délai d’envoi de la facture', '1 jour après la validation', 'select') + '</section></div></div>' +
      '<section class="card card-pad mt-14"><h3>Informations fiscales</h3><div class="form-grid cols-3 mt-14">' + formField('Forme juridique', 'SARL', 'select') + formField('SIRET', '812 345 678 00015') + formField('TVA intracommunautaire', 'FR12 812345678') + '</div><div style="text-align:right">' + Btn('Enregistrer les modifications', 'btn-primary', '', 'data-toast="Préférences enregistrées"') + '</div></section>';
    return U.providerShell(content, { active: 'quoteSettings', includeSettings: true, darkTop: true, wide: true });
  };

  P.revenues = function () {
    var content = U.pageHead('Mes revenus', 'Suivez vos gains, commissions et paiements.') +
      U.metrics([
        { title: 'Revenus totaux', value: '2 450 €', note: '+15% vs mois dernier', icon: 'wallet', color: 'green', trend: true },
        { title: 'Revenus ce mois', value: '950 €', note: '+12% vs mois dernier', icon: 'wallet', color: 'blue', trend: true },
        { title: 'En attente', value: '320 €', note: '3 paiements en attente', icon: 'clock', color: 'violet' },
        { title: 'Retirés', value: '2 130 €', note: 'Total des paiements reçus', icon: 'wallet', color: 'orange' },
        { title: 'Solde disponible', value: '320 €', note: 'Retirer mes gains', icon: 'wallet', color: 'cyan' }
      ]) +
      '<div class="grid layout-2-1"><div><section class="card"><div class="card-head"><h3>Évolution de vos revenus</h3><div>' + B('Revenus', 'blue') + ' ' + B('30J', 'blue') + '</div></div>' + U.lineChart({}) + '<div class="grid cols-4 card-pad"><div><small>Revenus moyens</small><strong>122 €</strong></div><div><small>Prestations</small><strong>20</strong></div><div><small>Commission moyenne</small><strong>12%</strong></div><div><small>Meilleur jour</small><strong class="text-green">14 mai</strong></div></div></section><section class="card"><div class="card-head"><h3>Historique des transactions</h3>' + Btn('Exporter', '', 'download', 'data-toast="Export généré"') + '</div>' + transactionTable() + '</section></div>' +
      '<aside><section class="card card-pad"><h3>Répartition des revenus</h3><div style="display:flex;gap:20px;align-items:center;margin-top:20px">' + U.donut('950 €', 'Total') + statusLegend() + '</div></section><section class="card card-pad"><h3>Statut des paiements</h3>' + progressLine('Paiements reçus', '2 130 €', 80, 'green') + progressLine('En attente', '320 €', 12, 'orange') + progressLine('Annulés / remboursés', '0 €', 0, '') + '</section><section class="card card-pad"><h3>Retirer mes gains</h3><div class="info-note success-note"><div><small>Solde disponible</small><br><strong style="font-size:24px">320 €</strong></div></div>' + Btn('Retirer mes gains', 'btn-primary btn-block mt-14', '', 'data-toast="Demande de retrait confirmée"') + '</section></aside></div>';
    return U.providerShell(content, { active: 'revenues', context: 'Mes revenus' });
  };

  function progressLine(label, value, percent, color) {
    return '<div style="margin-top:16px"><div class="detail-line"><strong>' + label + '</strong><span>' + value + ' &nbsp; ' + percent + '%</span></div><div class="progress ' + color + '"><span style="width:' + percent + '%"></span></div></div>';
  }

  function transactionTable() {
    var rows = [
      ['16 mai 2024', 'Dépannage court-circuit', 'Sophie Martin', '120 €', '-14,40 €', '105,60 €', 'Payé'],
      ['14 mai 2024', 'Installation prise extérieure', 'Thomas Bernard', '150 €', '-18,00 €', '132,00 €', 'Payé'],
      ['12 mai 2024', 'Mise aux normes', 'Pauline Leroy', '200 €', '-24,00 €', '176,00 €', 'En attente'],
      ['10 mai 2024', 'Installation luminaire', 'Julien Moreau', '80 €', '-9,60 €', '70,40 €', 'Payé']
    ];
    return '<div class="table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Prestation</th><th>Client</th><th>Montant</th><th>Commission</th><th>Vous recevez</th><th>Statut</th></tr></thead><tbody>' + rows.map(function (r) {
      return '<tr><td>' + r[0] + '</td><td><strong>' + r[1] + '</strong></td><td>' + r[2] + '</td><td><strong>' + r[3] + '</strong></td><td>' + r[4] + '</td><td><strong>' + r[5] + '</strong></td><td>' + B(r[6], r[6] === 'Payé' ? 'green' : 'orange') + '</td></tr>';
    }).join('') + '</tbody></table></div>';
  }

  P.quotes = function () {
    var content = U.pageHead('Mes devis', 'Gérez tous vos devis envoyés à vos clients.', Btn('Exporter', '', 'download', 'data-toast="Export des devis généré"')) +
      U.metrics([
        { title: 'Total devis', value: '18', note: '+3 ce mois', icon: 'file', trend: true },
        { title: 'En attente', value: '6', note: 'Soit 1 850 €', icon: 'clock', color: 'orange' },
        { title: 'Acceptés', value: '7', note: 'Soit 8 750 €', icon: 'check', color: 'green' },
        { title: 'Refusés', value: '3', note: 'Soit 0 €', icon: 'file', color: 'violet' },
        { title: 'Expirés', value: '2', note: 'À renouveler', icon: 'inbox' }
      ]) +
      '<div class="grid" style="grid-template-columns:minmax(0,1fr) 320px"><section class="card"><div class="card-head no-line">' + U.tabs([['all','Tous (18)'],['pending','En attente (6)'],['accepted','Acceptés (7)'],['refused','Refusés (3)'],['expired','Expirés (2)']], 'all') + '</div>' + U.filters('Rechercher un devis...', ['Toutes catégories', 'Période']) + quoteTable() + '<div class="center card-pad">' + Btn('Charger plus de devis', '', 'down', 'data-toast="Devis supplémentaires chargés"') + '</div></section>' + quoteInspector() + '</div>';
    return U.providerShell(content, { active: 'quotes', includeSettings: true, darkTop: true, wide: true });
  };

  function quoteTable() {
    var rows = [
      ['DEV-2024-018', 47, 'Sophie Martin', 'Rénovation salle de bain', '2 850 €', 'En attente', '16/05/2024'],
      ['DEV-2024-017', 13, 'Michel Dupont', 'Installation chauffe-eau', '650 €', 'Accepté', '15/05/2024'],
      ['DEV-2024-016', 45, 'Laura Bernard', 'Fuite d’eau + dépannage', '120 €', 'Refusé', '15/05/2024'],
      ['DEV-2024-015', 15, 'Thomas Legrand', 'Remplacement radiateur', '1 450 €', 'Accepté', '14/05/2024'],
      ['DEV-2024-014', 32, 'Camille Petit', 'Débouchage canalisation', '180 €', 'Expiré', '13/05/2024'],
      ['DEV-2024-013', 12, 'Julien Moreau', 'Installation robinetterie', '320 €', 'En attente', '13/05/2024'],
      ['DEV-2024-012', 47, 'Nathalie Blanc', 'Création salle de bain', '3 950 €', 'Accepté', '12/05/2024']
    ];
    return '<div class="table-wrap"><table class="data-table"><thead><tr><th>Devis</th><th>Client</th><th>Prestation</th><th>Montant</th><th>Statut</th><th>Créé le</th><th>Actions</th></tr></thead><tbody>' + rows.map(function (r, n) {
      var color = r[5] === 'Accepté' ? 'green' : r[5] === 'Refusé' ? 'red' : r[5] === 'En attente' ? 'orange' : '';
      return '<tr class="' + (n === 0 ? 'selected' : '') + '"><td><strong>' + r[0] + '</strong><small>N° ' + (18-n) + '</small></td><td><div class="table-avatar">' + A(r[1], 'avatar-sm') + '<div><strong>' + r[2] + '</strong><small>Lyon</small></div></div></td><td><strong>' + r[3] + '</strong></td><td><strong>' + r[4] + '</strong><small>TTC</small></td><td>' + B(r[5], color) + '</td><td>' + r[6] + '</td><td><div class="table-actions"><button class="icon-btn">' + I('eye','icon-sm') + '</button><button class="icon-btn">' + I('more','icon-sm') + '</button></div></td></tr>';
    }).join('') + '</tbody></table></div>';
  }

  function quoteInspector() {
    return '<aside class="inspector"><section class="card card-pad"><div class="page-head"><div>' + B('EN ATTENTE','orange') + '<h2 style="margin-top:8px">Devis DEV-2024-018</h2></div>' + I('download') + '</div><div class="list-row">' + A(47) + '<div class="list-main"><strong>Sophie Martin</strong><p>06 12 34 56 78<br>sophie.martin@email.fr<br>Lyon 3e</p></div></div>' + Btn('Voir la demande','btn-primary btn-block','', 'data-toast="Demande ouverte"') + '<div class="separator"></div><h3>Détails du devis</h3>' + U.detailLine('Prestation','Rénovation salle de bain') + U.detailLine('Créé le','16/05/2024') + U.detailLine('Validité','14 jours') + U.detailLine('Montant total','2 850,00 € TTC') + '<div class="separator"></div><h3>Résumé du devis</h3>' + U.detailLine('Main d’œuvre','1 850,00 €') + U.detailLine('Matériaux','750,00 €') + U.detailLine('TVA (10%)','150,00 €') + U.detailLine('Total TTC','2 850,00 €') + Btn('Marquer comme accepté','btn-success btn-block mt-14','check','data-toast="Devis accepté"') + Btn('Marquer comme refusé','btn-danger btn-block mt-14','x','data-toast="Devis refusé"') + Btn('Modifier le devis','btn-block mt-14','edit','data-toast="Éditeur de devis ouvert"') + '</section></aside>';
  }

  P.reviews = function () {
    var content = U.pageHead('Mes avis', 'Consultez et gérez les avis laissés par vos clients.', Btn('Exporter', '', 'download', 'data-toast="Export des avis généré"')) +
      U.metrics([
        { title: 'Note moyenne', value: '4,8/5', note: '+0,2 ce mois', icon: 'star', trend: true },
        { title: 'Avis reçus', value: '256', note: '+18 ce mois', icon: 'message', color: 'violet', trend: true },
        { title: 'Avis publiés', value: '243', note: '95%', icon: 'check', color: 'green' },
        { title: 'En attente', value: '8', note: 'À valider', icon: 'clock', color: 'orange' },
        { title: 'Avis masqués', value: '5', note: 'Par vous', icon: 'x', color: 'red' }
      ]) + '<div class="grid" style="grid-template-columns:minmax(0,1fr) 290px"><section class="card"><div class="card-head no-line">' + U.tabs([['all','Tous les avis (256)'],['published','Publiés (243)'],['pending','En attente (8)'],['hidden','Masqués (5)']], 'all') + '</div>' + U.filters('Rechercher un avis...', ['Toutes les notes', 'Toutes les prestations']) + reviewRows() + '<div class="center card-pad">' + Btn('Charger plus d’avis','','down','data-toast="Avis supplémentaires chargés"') + '</div></section>' + reviewSummary() + '</div>';
    return U.providerShell(content, { active: 'reviews', includeSettings: true, darkTop: true, wide: true });
  };

  function reviewRows() {
    var rows = [
      [47,'Sophie Martin','Rénovation salle de bain','5/5','Très satisfaite du travail réalisé ! L’équipe a été professionnelle, ponctuelle et à l’écoute.','Avis publié','green'],
      [13,'Michel Dupont','Installation chauffage','4/5','Bonne prestation, technicien très compétent et agréable.','Avis publié','green'],
      [45,'Laura Bernard','Débouchage canalisation','5/5','Intervention rapide et efficace un dimanche !','En attente de validation','orange'],
      [15,'Thomas Leroy','Remplacement radiateur','2/5','Déçu du service, le radiateur présente un problème de fuite après installation.','Avis masqué','red'],
      [32,'Camille Petit','Création salle de bain','5/5','Un grand merci pour cette superbe salle de bain !','Avis publié','green']
    ];
    return '<div class="section-list">' + rows.map(function (r) {
      return '<div class="list-row">' + A(r[0]) + '<div class="list-main" style="max-width:180px"><strong>' + r[1] + '</strong><p>' + r[2] + '<br>15/05/2024</p></div><div class="list-main"><strong><span class="stars">★★★★★</span> &nbsp; ' + r[3] + '</strong><p style="color:var(--ink)">' + r[4] + '</p><p class="text-' + r[6] + '">● ' + r[5] + '</p></div>' + Btn(r[6] === 'orange' ? 'Valider' : r[6] === 'red' ? 'Afficher' : 'Répondre','btn-sm','message','data-toast="Action effectuée"') + '<button class="icon-btn">' + I('more','icon-sm') + '</button></div>';
    }).join('') + '</div>';
  }

  function reviewSummary() {
    return '<aside class="inspector"><section class="card card-pad"><h3>Résumé des avis</h3><div style="font:800 40px Manrope;margin-top:20px">4,8<small style="font-size:18px">/5</small> <span class="stars" style="font-size:18px">★★★★★</span></div><p>Note moyenne globale</p><small class="text-muted">Basée sur 256 avis clients</small>' + progressLine('5 étoiles','186 (73%)',73,'orange') + progressLine('4 étoiles','52 (20%)',20,'orange') + progressLine('3 étoiles','12 (5%)',5,'orange') + progressLine('1 étoile','3 (1%)',1,'') + '<div class="separator"></div><h3>Évolution de la note</h3><div class="grid cols-2 mt-14"><strong>4,8/5<br><small>Ce mois</small></strong><strong>↗ +0,2<br><small>Progression</small></strong></div></section><section class="card card-pad"><h3>Avis les plus récents</h3><div class="list-row">' + A(47,'avatar-sm') + '<div class="list-main"><strong>Sophie Martin</strong><p class="stars">★★★★★</p></div></div><div class="list-row">' + A(13,'avatar-sm') + '<div class="list-main"><strong>Michel Dupont</strong><p class="stars">★★★★☆</p></div></div></section></aside>';
  }

  P.verification = function () {
    var steps = ['Vérification des profils','Informations générales','Détails de la prestation','Tarifs et options','Zone d’intervention','Photos et médias'];
    var checks = [
      ['Informations d’identité','Ajoutez une pièce d’identité en cours de validité.','Vérifié','green','user'],
      ['Justificatif d’activité','Fournissez un document officiel attestant de votre activité.','Téléverser un document','blue','building'],
      ['Assurance professionnelle','Ajoutez une attestation de responsabilité civile professionnelle.','Téléverser un document','blue','shield'],
      ['Vérification du numéro de téléphone','Nous envoyons un code par SMS pour vérifier votre numéro.','Vérifié','green','phone'],
      ['Vérification de l’adresse e-mail','Nous envoyons un lien de confirmation à votre adresse.','Vérifié','green','mail']
    ];
    var content = '<div class="breadcrumb">Accueil  ›  Vérification des profils</div>' + U.pageHead('Vérification des profils','Des prestataires authentiques et de confiance pour des services de qualité.',Btn('Enregistrer et quitter','','file','data-toast="Progression enregistrée"')) +
      '<div class="step-layout"><aside class="card steps"><h3>Étapes de création</h3>' + steps.map(function (x,n) { return '<div class="step ' + (n===0?'active':'') + '"><span class="step-num">' + (n+1) + '</span><div><strong>' + x + '</strong><small>' + (n===0?'Des prestataires authentiques':'Complétez cette étape') + '</small></div></div>'; }).join('') + '<div class="info-note">' + I('shield') + '<div><strong>Pourquoi vérifions-nous les profils ?</strong><br>Pour garantir l’authenticité et la sécurité de la plateforme.</div></div></aside>' +
      '<section class="card card-pad"><div class="list-row"><span class="list-icon">' + I('shield','icon-lg') + '</span><div class="list-main"><h2>Faites vérifier votre profil</h2><p>Complétez votre profil et ajoutez les documents nécessaires pour obtenir le badge « Prestataire vérifié ».</p></div></div><div class="info-note success-note">' + I('check','icon-lg') + '<div><strong>Devenez un prestataire vérifié</strong><br>Les profils vérifiés obtiennent jusqu’à 3x plus de demandes.</div></div><h3 style="margin:18px 0 4px">Étapes de vérification</h3><div class="section-list" style="padding:0">' + checks.map(function (r) { return '<div class="list-row card" style="margin-top:8px;padding:10px"><span class="list-icon">' + I(r[4]) + '</span><div class="list-main"><strong>' + r[0] + '</strong><p>' + r[1] + '</p></div>' + (r[3]==='green'?B('✓ '+r[2],'green'):Btn(r[2],'btn-sm','upload','data-toast="Sélecteur de document ouvert"')) + '</div>'; }).join('') + '</div><div class="info-note">' + I('lock') + '<div><strong>Vos documents sont sécurisés et confidentiels.</strong><br>Ils ne sont utilisés que pour la vérification de votre profil.</div></div><div style="display:flex;justify-content:space-between;margin-top:18px">' + Btn('Précédent','','chevron') + Btn('Suivant','btn-primary','chevron','data-toast="Étape suivante"') + '</div></section>' +
      '<aside><section class="card card-pad"><h3>Statut de vérification</h3><div class="verification-progress"></div><p class="center fw-700">Profil en cours de vérification</p><div class="separator"></div><p class="text-green">● Informations d’identité</p><p>○ Justificatif d’activité</p><p>○ Assurance professionnelle</p><p class="text-green">● Téléphone</p><p class="text-green">● E-mail</p></section><section class="card card-pad"><h3>Badge prestataire vérifié</h3><div class="list-row">' + A(12) + '<div class="list-main"><strong>Plombier chauffagiste</strong><p class="stars">★★★★★ 4,8</p></div></div><p>✓ Badge visible sur votre profil</p><p>✓ Plus de visibilité dans les résultats</p></section></aside></div>';
    return U.publicShell(content, {});
  };

  P.companyInfo = function () {
    var content = U.pageHead('Paramètres','Accueil  ›  Paramètres  ›  Informations') + U.settingsTabs('companyInfo',false) +
      '<div class="grid layout-2-1"><div><section class="card card-pad"><div class="page-head"><h3>Informations de l’entreprise</h3><span class="text-muted">Les champs marqués d’un * sont obligatoires</span></div><div class="form-grid">' + formField('Nom de l’entreprise *','Plomberie Express','text',true) + formField('Slogan','Votre expert plomberie à Lyon et ses environs','text',true) + formField('Description *','Plomberie Express est une entreprise spécialisée dans les travaux de plomberie, dépannage, installation et rénovation. Nous intervenons rapidement à Lyon et dans toute la métropole avec professionnalisme et transparence.','textarea',true) + formField('Année de création *','2015') + formField('Siret *','812 345 678 00015') + formField('N° TVA intracommunautaire','FR12 812345678') + formField('Forme juridique','SARL','select') + formField('Capital social','10 000 €') + formField('Effectif','3 - 10 salariés','select') + '</div></section><section class="card card-pad"><h3>Adresse de l’entreprise</h3><div class="form-grid cols-3 mt-14">' + formField('Adresse *','15 rue des Fleurs','text',true) + formField('Code postal *','69003') + formField('Ville *','Lyon') + formField('Pays *','France','select') + '</div>' + Btn('Enregistrer les modifications','btn-primary mt-14','','data-toast="Informations enregistrées"') + '</section></div>' +
      '<aside><section class="card card-pad"><h3>Logo et bannière</h3><div class="list-row"><span class="list-icon">' + I('building','icon-lg') + '</span><div class="list-main"><strong>Plomberie Express</strong></div>' + Btn('Modifier le logo','btn-sm','edit','data-toast="Sélecteur de logo ouvert"') + '</div><div style="height:120px;border-radius:8px;background:linear-gradient(90deg,#274d75,#082f5e);color:#fff;display:grid;place-items:center;text-align:center;font-weight:800">PLOMBERIE EXPRESS<br>Votre expert plombier à Lyon</div>' + Btn('Modifier la bannière','btn-block mt-14','edit','data-toast="Sélecteur de bannière ouvert"') + '</section><section class="card card-pad"><h3>Informations de contact</h3>' + detailLineIcon('phone','06 12 34 56 78') + detailLineIcon('mail','contact@plomberie-express.fr') + detailLineIcon('map','www.plomberie-express.fr') + '</section><section class="card card-pad"><h3>Réseaux sociaux</h3>' + detailLineIcon('message','Facebook · plomberieexpress') + detailLineIcon('image','Instagram · plomberieexpress') + '</section></aside></div>';
    return U.providerShell(content,{active:'companyInfo',includeSettings:true,darkTop:true,wide:true});
  };

  function detailLineIcon(iconName,text) {
    return '<div class="list-row"><span class="text-blue">' + I(iconName) + '</span><div class="list-main"><strong>' + text + '</strong></div><a class="text-blue fw-700" href="#">Modifier</a></div>';
  }
})();
