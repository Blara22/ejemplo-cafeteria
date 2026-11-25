// LÓGICA DE VALIDACIÓN: funciones puras, no tocan el DOM.
// Exportaciones nombradas: varias por archivo.
export const esNombreValido = (v) => v.trim().length >= 3;

export const esEmailValido = (v) => /^\S+@\S+\.\S+$/.test(v);

export const existeProducto = (productos, nombre) =>
  productos.some((p) => p.nombre.toLowerCase() === nombre.trim().toLowerCase());

// Devuelve un objeto { campo: "mensaje" }. Vacío = todo bien.
export function validarPedido(carrito) {
  const errores = {};
  if (carrito.length === 0) errores.carrito = "Agrega al menos un producto";
  return errores;
}
