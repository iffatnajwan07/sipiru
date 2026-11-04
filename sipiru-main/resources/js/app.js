import Alpine from 'alpinejs';
import buildingForm from './components/building-form';
import roomForm from './components/room-form';
import bookingForm from './components/booking-form';

window.Alpine = Alpine;

Alpine.data('buildingForm', buildingForm);
Alpine.data('roomForm', roomForm);
Alpine.data('bookingForm', bookingForm);

Alpine.start();
