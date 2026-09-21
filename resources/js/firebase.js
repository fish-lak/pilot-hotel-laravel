import { getAnalytics } from 'firebase/analytics';
import { initializeApp } from 'firebase/app';

const firebaseConfig = {
	apiKey: 'AIzaSyAyf4-n4sD_RcHqpSi5HESIja_Ug0reN0c',
	authDomain: 'pilot-hotel2026.firebaseapp.com',
	databaseURL: 'https://pilot-hotel2026-default-rtdb.firebaseio.com',
	projectId: 'pilot-hotel2026',
	storageBucket: 'pilot-hotel2026.firebasestorage.app',
	messagingSenderId: '29901210764',
	appId: '1:29901210764:web:f9307e041faba31a71e7a9',
	measurementId: 'G-XTGBBYR2JC',
};

const firebaseApp = initializeApp(firebaseConfig);
const analytics = getAnalytics(firebaseApp);

export { analytics, firebaseApp };