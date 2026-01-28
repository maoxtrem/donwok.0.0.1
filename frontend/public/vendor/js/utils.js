async function enviarDatos(url, formData , method = 'POST') {
  try {

    const response = await fetch(url, {
      method,
      body: formData
    });

    const result = await response.json();
    console.log('Respuesta del servidor:', result);

    if (result.status === 'error') {
      Swal.fire({
        title: '¡Server!',
        text: result.message,
        icon: result.status,
        confirmButtonText: 'Aceptar'
      });
      return null;
    }

    return result;
  } catch (error) {
    console.error('Error al enviar:', error);
    Swal.fire({
      title: 'Error',
      text: 'No se pudo enviar la petición',
      icon: 'error',
      confirmButtonText: 'Aceptar'
    });
    return null;
  }
}
