import { createWpViteConfig } from 'pressbooks-build-tools';
import { resolve } from 'path';

export default createWpViteConfig({
	input: {
		app: resolve(__dirname, 'resources/assets/js/pressbooks-borges.js'),
	},
	outDir: 'dist',
});
