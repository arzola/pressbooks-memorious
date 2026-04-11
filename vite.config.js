import { createWpViteConfig } from 'pressbooks-build-tools';
import { resolve } from 'path';

export default createWpViteConfig({
	input: {
		app: resolve(__dirname, 'assets/src/scripts/pressbooks-beacon.js'),
	},
	outDir: 'assets/dist',
});
