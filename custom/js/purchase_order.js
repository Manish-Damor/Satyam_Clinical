// Purchase Order JavaScript Functions

$(document).ready(function () {
  // Any purchase order specific JavaScript can go here
});

function calculateTotal(quantity, unitPrice) {
  return (quantity * unitPrice).toFixed(2);
}

function formatCurrency(value) {
  return (
    "₹" +
    parseFloat(value)
      .toFixed(2)
      .replace(/\B(?=(\d{3})+(?!\d))/g, ",")
  );
}
