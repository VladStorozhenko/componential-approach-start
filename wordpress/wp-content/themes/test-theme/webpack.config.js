const webpack = require('webpack');
const path = require('path');
const {CleanWebpackPlugin} = require('clean-webpack-plugin');
const TerserWebpackPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

const themePath = '/wp-content/themes/test-theme';

const isDev = process.env.NODE_ENV === 'development';

let mode;
isDev ? mode = 'development' : mode = 'production';

const manifest = require('./builder-scripts/entry-points.json');

const filename = ext => isDev ? `[name].${ext}` : `[name]~[hash].${ext}`;

module.exports = {
	context: path.resolve(__dirname, 'builder-scripts'),
	mode,
	// Пути в файле manifest.js указываются от src, т.к. задано поле context.
	entry: manifest,
	output: {
		filename: filename('js'),
		path: path.resolve(__dirname, 'bundles'),
	},
	optimization: {
		minimize: true,
		minimizer: [new TerserWebpackPlugin(), new CssMinimizerPlugin()],
		splitChunks: {
			chunks(chunk) {
				return chunk.name !== 'bound' && chunk.name !== 'polyfills';
			},
		},
	},
	devtool: isDev ? 'source-map' : false,
	plugins: [
		new CleanWebpackPlugin({cleanStaleWebpackAssets:false}),
		new MiniCssExtractPlugin({
			filename: isDev ? '[name].css' : '[name]~[hash].css',
			chunkFilename: isDev ? 'chunk-[id].css' : 'chunk-[id]~[hash].css',
			ignoreOrder: false, // Enable to remove warnings about conflicting order
		}),
		new ImageminPlugin({
			test: /\.(jpe?g|png|gif|svg)$/i,
			pngquant: {quality: '95-100'},
		}),
		// new webpack.optimize.LimitChunkCountPlugin({
		// 	// Задаю максимальное количество соедияемых чанков.
		// 	// В какой-то момент чанков было очень много и вместо нужно названия чанк стал называться ...~e77c9232.js в результате я не мог подключить его на странице нормально через manifest-adding.
		// 	maxChunks: 10,
		// }),

		// new BundleAnalyzerPlugin( {
		// 	analyzerHost: 'fin.test',
		// 	// analyzerHost: '0.0.0.0',
		// 	analyzerMode: 'server',
		// 	analyzerPort: 8888
		// } ),
		// new LiveReloadPlugin(),
	],
	module: {
		rules: [
			//babel
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
				}
			},
			//vue
			{
				test: /\.vue$/,
				loader: 'vue-loader'
			},
			//css
			{
				test: /\.(sass|scss|css)$/,
				use: [
					{
						loader: MiniCssExtractPlugin.loader,
						options: {
							publicPath: themePath + '/bundles/',
						},
					},
					'css-loader',
					'sass-loader'
				],
			},
			//img
			{
				test: /\.(png|jpg|svg|gif)$/,
				use: ['file-loader'],
			},
			//fonts
			{
				test: /\.(ttf|woff|woff2|eot)$/,
				use: ['file-loader']
			}
		]
	},
}
