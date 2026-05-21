window.GF = window.GF || {};

window.GF.createAjaxDataTable = function (selector, config) {
  var $table = $(selector);
  if (!$table.length || typeof $.fn.DataTable === 'undefined') {
    return null;
  }

  var options = $.extend(true, {}, config || {});
  var ajaxData = options.ajaxData;
  delete options.ajaxData;

  if (options.ajax && ajaxData) {
    if (typeof options.ajax === 'string') {
      options.ajax = { url: options.ajax };
    }

    var previousData = options.ajax.data;
    options.ajax.data = function (d) {
      if (typeof previousData === 'function') {
        previousData(d);
      } else if (previousData && typeof previousData === 'object') {
        $.extend(d, previousData);
      }

      if (typeof ajaxData === 'function') {
        $.extend(d, ajaxData());
      } else if (ajaxData && typeof ajaxData === 'object') {
        $.extend(d, ajaxData);
      }
    };
  }

  var defaults = {
    processing: true,
    serverSide: false,
    ajax: null,
    columns: [],
    responsive: true,
    scrollX: false,
    autoWidth: true,
    order: false,
    language: {
      search: 'Buscar:',
      searchPlaceholder: 'Buscar...',
      processing: 'Procesando...',
      lengthMenu: 'Mostrar _MENU_ registros',
      info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
      infoEmpty: 'Mostrando 0 a 0 de 0 registros',
      infoFiltered: '(filtrado de _MAX_ registros totales)',
      infoPostFix: '',
      loadingRecords: 'Cargando...',
      zeroRecords: 'No se encontraron resultados',
      emptyTable: 'No hay datos disponibles en la tabla',
      paginate: {
        first: 'Primero',
        previous: 'Anterior',
        next: 'Siguiente',
        last: 'Ultimo'
      },
      aria: {
        sortAscending: ': activar para ordenar la columna de manera ascendente',
        sortDescending: ': activar para ordenar la columna de manera descendente'
      }
    }
  };

  return $table.DataTable($.extend(true, {}, defaults, options));
};

window.GF.initSelect2 = function (root) {
  if (typeof $.fn.select2 === 'undefined') {
    return;
  }

  var $root = root ? $(root) : $(document);
  $root.find('select').each(function () {
    var $select = $(this);
    if ($select.hasClass('select2-hidden-accessible') || $select.closest('.dt-container').length) {
      return;
    }

    var modalParent = $select.closest('.modal');
    var options = {
      width: '100%'
    };

    if (modalParent.length) {
      options.dropdownParent = modalParent;
    }

    $select.select2(options);
  });
};

window.GF._ensureAlertModal = function () {
  var modalId = 'gf-alert-modal';
  var existing = document.getElementById(modalId);
  if (existing) {
    return existing;
  }

  var template = ''
    + '<style id="gf-alert-modal-style">'
    + '.gf-alert-modal .modal-content{border:1px solid rgba(255,255,255,.08);border-radius:1rem;box-shadow:0 20px 60px rgba(0,0,0,.45);background:linear-gradient(180deg,rgba(38,43,73,.98) 0%,rgba(28,33,59,.98) 100%);}'
    + '.gf-alert-modal .modal-body{padding:2rem 1.5rem;}'
    + '.gf-alert-modal .gf-alert-icon-wrap{box-shadow:0 10px 30px rgba(0,0,0,.2), inset 0 0 0 1px rgba(255,255,255,.08);}'
    + '.gf-alert-modal .gf-alert-title{font-weight:700;font-size:1.35rem;letter-spacing:.2px;}'
    + '.gf-alert-modal .gf-alert-subtitle{font-size:.98rem;line-height:1.45;max-width:420px;margin-left:auto;margin-right:auto;}'
    + '.gf-alert-modal .btn{min-width:118px;font-weight:600;border-radius:.65rem;}'
    + '.gf-alert-modal .btn-primary,.gf-alert-modal .btn-success,.gf-alert-modal .btn-warning,.gf-alert-modal .btn-danger{box-shadow:0 8px 18px rgba(0,0,0,.25);}'
    + '.gf-alert-modal.fade .modal-dialog{transform:translateY(10px) scale(.97);opacity:0;transition:transform .2s ease,opacity .2s ease;}'
    + '.gf-alert-modal.show .modal-dialog{transform:translateY(0) scale(1);opacity:1;}'
    + '</style>'
    + '<div class="modal fade" id="' + modalId + '" tabindex="-1" aria-hidden="true">'
    + '  <div class="modal-dialog modal-dialog-centered gf-alert-modal">'
    + '    <div class="modal-content">'
    + '      <div class="modal-body text-center p-4">'
    + '        <div class="gf-alert-icon-wrap mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle" style="width:88px;height:88px;" id="gf-alert-icon-wrap">'
    + '          <i class="ti" id="gf-alert-icon" style="font-size:42px;"></i>'
    + '        </div>'
    + '        <h4 class="gf-alert-title mb-1" id="gf-alert-title">Titulo</h4>'
    + '        <p class="gf-alert-subtitle text-muted mb-4" id="gf-alert-subtitle">Subtitulo</p>'
    + '        <div class="d-flex justify-content-center gap-2" id="gf-alert-actions"></div>'
    + '      </div>'
    + '    </div>'
    + '  </div>'
    + '</div>';

  document.body.insertAdjacentHTML('beforeend', template);
  return document.getElementById(modalId);
};

window.GF._showAlertModal = function (options) {
  if (typeof bootstrap === 'undefined') {
    return;
  }

  var modalEl = window.GF._ensureAlertModal();
  var titleEl = document.getElementById('gf-alert-title');
  var subtitleEl = document.getElementById('gf-alert-subtitle');
  var iconEl = document.getElementById('gf-alert-icon');
  var iconWrap = document.getElementById('gf-alert-icon-wrap');
  var actionsEl = document.getElementById('gf-alert-actions');

  titleEl.textContent = options.title || 'Aviso';
  subtitleEl.textContent = options.subtitle || '';
  iconEl.className = 'ti ' + (options.icon || 'tabler-info-circle');
  iconEl.style.color = options.iconColor || '#696cff';
  iconWrap.style.background = options.iconBg || 'rgba(105,108,255,.15)';

  actionsEl.innerHTML = '';
  var primaryButton = null;
  (options.buttons || []).forEach(function (button) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = button.className || 'btn btn-primary';
    btn.textContent = button.text || 'Aceptar';
    btn.addEventListener('click', function () {
      if (typeof button.onClick === 'function') {
        button.onClick();
      }
      modal.hide();
    });
    if (button.primary) {
      primaryButton = btn;
    }
    actionsEl.appendChild(btn);
  });

  var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  var dialog = modalEl.querySelector('.modal-dialog');
  if (dialog) {
    dialog.classList.add('gf-alert-modal');
    if (options.sizeClass) {
      dialog.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
      dialog.classList.add(options.sizeClass);
    }
  }

  modalEl.addEventListener('shown.bs.modal', function handleShown() {
    if (primaryButton) {
      primaryButton.focus();
    }
    modalEl.removeEventListener('shown.bs.modal', handleShown);
  });
  modal.show();
};

window.GF.showModalSuccess = function (title, subtitle, callback) {
  window.GF._showAlertModal({
    title: title || 'Operacion exitosa',
    subtitle: subtitle || '',
    icon: 'tabler-circle-check',
    iconColor: '#28c76f',
    iconBg: 'rgba(40,199,111,.15)',
    buttons: [
      {
        text: 'Aceptar',
        className: 'btn btn-success',
        primary: true,
        onClick: callback
      }
    ],
    sizeClass: 'modal-sm'
  });
};

window.GF.showModalError = function (title, subtitle, callback) {
  window.GF._showAlertModal({
    title: title || 'Ocurrio un error',
    subtitle: subtitle || '',
    icon: 'tabler-alert-circle',
    iconColor: '#ea5455',
    iconBg: 'rgba(234,84,85,.15)',
    buttons: [
      {
        text: 'Aceptar',
        className: 'btn btn-danger',
        primary: true,
        onClick: callback
      }
    ],
    sizeClass: 'modal-sm'
  });
};

window.GF.showModalWarning = function (title, subtitle, callback) {
  window.GF._showAlertModal({
    title: title || 'Advertencia',
    subtitle: subtitle || '',
    icon: 'tabler-alert-triangle',
    iconColor: '#ff9f43',
    iconBg: 'rgba(255,159,67,.15)',
    buttons: [
      {
        text: 'Aceptar',
        className: 'btn btn-warning',
        primary: true,
        onClick: callback
      }
    ],
    sizeClass: 'modal-sm'
  });
};

window.GF.showModalConfirm = function (title, subtitle, onConfirm, onCancel) {
  window.GF._showAlertModal({
    title: title || 'Confirmar accion',
    subtitle: subtitle || 'Esta accion no se puede deshacer.',
    icon: 'tabler-help-circle',
    iconColor: '#ff9f43',
    iconBg: 'rgba(255,159,67,.15)',
    buttons: [
      {
        text: 'Cancelar',
        className: 'btn btn-label-secondary',
        onClick: onCancel
      },
      {
        text: 'Confirmar',
        className: 'btn btn-warning',
        primary: true,
        onClick: onConfirm
      }
    ],
    sizeClass: 'modal-sm'
  });
};

window.GF._resolveDeleteName = function (button, form) {
  var directName = button.getAttribute('data-gf-name') || (form ? form.getAttribute('data-gf-name') : '');
  if (directName) {
    return directName.trim();
  }

  var row = button.closest('tr');
  if (!row) {
    return '';
  }

  var firstCell = row.querySelector('td');
  if (!firstCell) {
    return '';
  }

  return (firstCell.textContent || '').replace(/\s+/g, ' ').trim();
};

window.GF._resolveDeleteMessage = function (button, form, fallbackMessage) {
  var entity = button.getAttribute('data-gf-entity') || (form ? form.getAttribute('data-gf-entity') : '') || 'este registro';
  var name = window.GF._resolveDeleteName(button, form);
  var messageType = button.getAttribute('data-gf-confirm') || '';
  var fallback = (fallbackMessage || '').trim();

  if (messageType === 'auto') {
    if (name) {
      return 'Seguro que quieres eliminar ' + entity + ' "' + name + '"?';
    }
    return 'Seguro que quieres eliminar ' + entity + '?';
  }

  if (/^seguro\??$/i.test(fallback) || /^seguro\?\s*$/i.test(fallback) || /eliminar/i.test(fallback)) {
    if (name) {
      return 'Seguro que quieres eliminar ' + entity + ' "' + name + '"?';
    }
    return 'Seguro que quieres eliminar ' + entity + '?';
  }

  return fallback || 'Deseas continuar?';
};

window.GF.bindConfirmModals = function (root) {
  var scope = root && root.querySelectorAll ? root : document;
  var forms = scope.querySelectorAll('form[onsubmit*="confirm("]');
  var submitButtons = scope.querySelectorAll('button[type="submit"][onclick*="confirm("], input[type="submit"][onclick*="confirm("], button[type="submit"][data-gf-confirm], input[type="submit"][data-gf-confirm]');

  forms.forEach(function (form) {
    if (form.dataset.gfConfirmBound === '1') {
      return;
    }

    var onsubmit = form.getAttribute('onsubmit') || '';
    var match = onsubmit.match(/confirm\((['"])(.*?)\1\)/);
    var message = match ? match[2] : 'Deseas continuar?';

    form.dataset.gfConfirmMessage = message;
    form.dataset.gfConfirmBound = '1';
    form.removeAttribute('onsubmit');

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var submitForm = form;
      window.GF.showModalConfirm('Confirmar accion', submitForm.dataset.gfConfirmMessage, function () {
        submitForm.submit();
      });
    });
  });

  submitButtons.forEach(function (button) {
    if (button.dataset.gfConfirmBound === '1') {
      return;
    }

    var message = button.getAttribute('data-gf-confirm') || '';
    if (!message) {
      var onclick = button.getAttribute('onclick') || '';
      var match = onclick.match(/confirm\((['"])(.*?)\1\)/);
      message = match ? match[2] : 'Deseas continuar?';
    }

    button.dataset.gfConfirmMessage = message;
    button.dataset.gfConfirmBound = '1';
    button.removeAttribute('onclick');

    button.addEventListener('click', function (event) {
      var form = button.closest('form');
      if (!form) {
        return;
      }

      event.preventDefault();
      var resolvedMessage = window.GF._resolveDeleteMessage(button, form, button.dataset.gfConfirmMessage);
      window.GF.showModalConfirm('Confirmar accion', resolvedMessage, function () {
        form.submit();
      });
    });
  });

  if (!window.GF._delegatedConfirmBound) {
    window.GF._delegatedConfirmBound = true;
    document.addEventListener('click', function (event) {
      var button = event.target.closest('button[type="submit"][data-gf-confirm], input[type="submit"][data-gf-confirm]');
      if (!button) {
        return;
      }

      var form = button.closest('form');
      if (!form) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      var resolvedMessage = window.GF._resolveDeleteMessage(button, form, 'Deseas continuar?');
      window.GF.showModalConfirm('Confirmar accion', resolvedMessage, function () {
        form.submit();
      });
    }, true);
  }
};

$(function () {
  window.GF.initSelect2(document);
  window.GF.bindConfirmModals(document);
  $(document).on('shown.bs.modal', function (event) {
    window.GF.initSelect2(event.target);
    window.GF.bindConfirmModals(event.target);
  });
});
