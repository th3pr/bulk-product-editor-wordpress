// jQuery(document).ready(function($) {
//     // Object to store current sorting order for each column
//     var sortingOrders = {};

//     // Sorting function
//     $('.sortable').click(function(){
//         var $this = $(this);
//         var column = $this.data('column');

//         // Determine current sorting order or default to 'asc' if not set
//         var currentOrder = sortingOrders[column] || '';

//         // Determine new sorting order
//         var newOrder = currentOrder === 'asc' ? 'desc' : 'asc';

//         // Remove sorting classes from all other columns
//         $('.sortable').removeClass('asc desc');

//         // Update sorting class for the current column
//         $this.removeClass('asc desc').addClass(newOrder);

//         // Update sorting order for the column
//         sortingOrders[column] = newOrder;

//         // Update query parameters
//         var url = new URL(window.location.href);
//         url.searchParams.set('orderby', column);
//         url.searchParams.set('order', newOrder);
//         window.location.href = url.toString();
//     });

//     // Set initial sorting order for each column based on the URL parameters
//     $('.sortable').each(function() {
//         var $this = $(this);
//         var column = $this.data('column');
//         var currentOrder = sortingOrders[column];
        
//         if (currentOrder === 'asc') {
//             $this.addClass('asc');
//         } else if (currentOrder === 'desc') {
//             $this.addClass('desc');
//         }
//     });
// });

// jQuery(document).ready(function($) {
//     // Dropdown change event
//     $('#sorting-dropdown').change(function() {
//         var selectedValue = $(this).val();
        
//         // Update query parameters based on the selected value
//         var url = new URL(window.location.href);
//         url.searchParams.set('orderby', selectedValue.split('_')[0]);
//         url.searchParams.set('order', selectedValue.split('_')[1]);
//         window.location.href = url.toString();
//     });
// });
