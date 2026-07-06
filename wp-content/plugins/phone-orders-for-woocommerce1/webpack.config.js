const path = require('path');
const {VueLoaderPlugin} = require('vue-loader');
const TerserJSPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const OptimizeCssAssetsPlugin = require('css-minimizer-webpack-plugin');
const MomentLocalesPlugin = require('moment-locales-webpack-plugin');
const {DefinePlugin} = require('webpack');

module.exports = (env, argv) => {
    const isDevelopment = argv.mode === 'development';

    return {
        entry: './assets/js/app.js',
        output: {
            filename: 'js/build-app.js',
            path: path.resolve(__dirname, 'assets'),
        },
        resolve: {
            alias: {
                'vue$': 'vue/dist/vue.esm-bundler.js',
            },
        },
        module: {
            rules: [{
                test: /\.vue$/,
                loader: 'vue-loader',
            }, {
                test: /.js$/,
                exclude: [/node_modules/],
                use: [{
                    loader: 'babel-loader',
                }]
            }, {
                test: /\.css$/,
                use: [
                    //'vue-style-loader',
                    {
                        loader: MiniCssExtractPlugin.loader,
                        options: {
                            esModule: false,
                        },
                    },
                    'css-loader'
                ]
            }, {
                test: /\.less$/,
                use: [
                    //  'vue-style-loader',
                    {
                        loader: MiniCssExtractPlugin.loader,
                        options: {
                            esModule: false,
                        },
                    },
                    'css-loader',
                    'less-loader'
                ]
            },],
        },
        plugins: [
            new VueLoaderPlugin(),
            new MiniCssExtractPlugin({
                filename: 'css/bundle.css',
            }),
            new MomentLocalesPlugin(),
            new DefinePlugin({
                __VUE_OPTIONS_API__: true,
                __VUE_PROD_DEVTOOLS__: isDevelopment,
            })
        ],
        optimization: {
            minimizer: [
                new TerserJSPlugin({
                    terserOptions: {
                        compress: {
                            drop_console: true,
                        },
                        output: {
                            comments: false,
                        }
                    },
                    extractComments: false,
                }),
                new OptimizeCssAssetsPlugin({})
            ],
        },
    }
};