function habilitarElemento(idDisparador, idElemento) {
    var elemento = document.getElementById(idElemento);
    elemento.removeAttribute('disabled');
    elemento.setAttribute('required', 'required');
    var disparador = document.getElementById(idDisparador);
    disparador.setAttribute('onclick', 'deshabilitarElemento(\'' + idDisparador + '\',\'' + idElemento + '\')');
}

function deshabilitarElemento(idDisparador, idElemento) {
    var elemento = document.getElementById(idElemento);
    elemento.setAttribute('disabled', 'true');
    elemento.removeAttribute('required');
    var disparador = document.getElementById(idDisparador);
    disparador.setAttribute('onclick', 'habilitarElemento(\'' + idDisparador + '\',\'' + idElemento + '\')');
}

function confirmDeleteProperty(id) {
    var action = document.getElementById('confirmDeleteProperty');
    action.setAttribute('href', 'propiedad/' + id);
}

function newGenericProperty(ruta) {
    var inputRuta = document.getElementById('configorion_configorionbundle_archivonewgenericpropertytype_newGenericRuta');
    inputRuta.setAttribute('value', ruta);

    $('#collapseTwo').collapse('hide');

    $('#collapseThree').collapse('show');
}

function deleteGenericProperty(ruta) {
    var hiddenRuta = document.getElementById('configorion_configorionbundle_archivodeletegenericpropertytype_deleteGenericProperty');
    hiddenRuta.setAttribute('value', ruta);
}

function archivarModificaciones() {
    $('#modalArchivarModificaciones').modal('hide')
    var form = document.getElementById('formArchivarModificaciones');
    form.submit();
}

function setFavorito(ruta) {
    var favorito = document.getElementById('apply_favorito');
    favorito.setAttribute('action', ruta);
}

function setModificacion(id_modificacion) {
    var configuracion = document.getElementById('form_perfiles_id_modificacion');
    configuracion.setAttribute('value', id_modificacion);
}

/*
 * input: Id del campo input
 * urlPath: Twig {{ url('archivo_autocompletado')}}
 * type: 'directorio' ó 'archivo' 
 */
function autocompletadoDirRequest(input, urlPath) {
    $(document).ready(function() {
        $(input).autocomplete({
            source: ['']
        });
        $(input).keyup(function() {
            var service = $(input).val();
            var size = service.length - 1;
            if (service[size] === '/') {
                var dataString = 'search=' + service + '&type=directorio';
                $.ajax({
                    type: "POST",
                    url: urlPath,
                    data: dataString,
                    success: function(data) {
                        var availableTags = jQuery.parseJSON(data);
                        $(input).autocomplete({
                            source: availableTags
                        });
                    }
                });
            }
        });
    });
}

function autocompletadoFileRequest(inputIn, inputOut, urlPath) {
    $(document).ready(function() {
        $(inputOut).autocomplete({
            source: ['']
        });
        $(inputOut).keyup(function() {
            var service = $(inputIn).val() + '/';
            var dataString = 'search=' + service + '&type=archivo';
            $.ajax({
                type: "POST",
                url: urlPath,
                data: dataString,
                success: function(data) {
                    var availableTags = jQuery.parseJSON(data);
                    if (availableTags !== null) {
                        $(inputOut).autocomplete({
                            source: availableTags
                        });
                    }
                }
            });
        });
    });
}

function importFavorito() {
    $('#modalImportarFavoritos').modal('hide')
    var form = document.getElementById('formImportarFavoritos');
    form.submit();
}

function setActionReporte(route) {
    $('#form-report').attr('action', route);
}