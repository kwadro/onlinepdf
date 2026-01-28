import { startStimulusApp } from '@symfony/stimulus-bridge';

// запускаємо Stimulus
export const app = startStimulusApp(require.context(
    './controllers',
    true,
    /\.js$/
));

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
