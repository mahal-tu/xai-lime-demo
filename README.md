# Web Experiment for the Evaluation of the Understandability of XAI Visualizations

## Usage

* Install packages from `requirements.txt`
* Run notebook `ki2026.ipynb`
  - train real and bogus classifier
  - create LIME explanations of randomly chosen interesting cases
  - prepare PHP data for inclusion into the web application
  - the random seed is stored in `random-seed.txt` for reproducibility. Backup/delete this file depending of your use case
* Run `copy-imgs-to-web-experiment.sh` to copy your visualizations to the web experiment
  - Manual workaround: Copy `cases.php` and the `lime-imgs` folder to `php/www/html/xai`
* cd to the `php` folder
* Run `docker compose up` to start the web experiment locally
  - User interface available at <http://localhost:8080>
  - Database view at <http://localhost:8081>

## Adapting to new Image Data

The code expects four variables

* `x`: input image data, one image per row. Images must be quadratic.
* `y`: labels / ground truth for each image. Must be integers starting with 0. Array must have the same length as `x`
* `label_names`: textual descriptions of the labels. Will be indexed using the data from `y`
* `n_channels`: 1 for gray scale images, 3 for RGB color images

The first code cell in `ki2026.ipynb` demonstrates their use. Adaption to other data sets should be straightforward.

Visualization style and number of examples can be tweaked in the "Create LIME Visualizations" section. The number of trials depends on the `N_SAMPLES` variable. Depending on the sizes of the inputs, internal parameters of the image segmentation algorithms used might need to be reviewed.

## Deployment to the Web

The web application should run on any web server that provides PHP and MySQL or MariaDB (typical Wordpress setup). Before deployment:

* Adapt database host and credentials in `www/.env` and make sure that this file is readable by the web server, but will not be served to clients. Consult your server administrator before deployment.
* Change the download password in `www/.env`
* Set `XAI_DEBUG=0` in `www/.env`
* Adapt imprint and data privacy notice. Consult your data protection officer if in doubt.
* Copy the complete `xai` folder to the server. `www/html/index.html` is only a helper for local debug runs.

## Defining Participant Groups

Use different values for the `?ref=` parameter, e.g. `https://mypage.edu/xai?ref=students` to define groups. The value passed here will be stored with the participant data in the database.

## Data Analysis

* `stats.php` provides general statistics, e.g., number of completed trials, for monitoring an ongoing experiment.
* `download.php` provides download links for the collected data during the different parts of the experiment. Download all data and place it in the `analysis` folder.
* Use `analysis.R` as a starting point of your data analysis.


## Publication

Halbrügge, M (2026). A New Paradigm for the Empirical Evaluation of the Understandability of LIME Visualizations. In _1st Workshop on Explainability, Transparency, and Safety (ExTraSafe)_, Bremen, Germany

---

# General Introduction To LIME

Based on Local Interpretable Model-agnostic Explanations (LIME)

Ribeiro, M. T., Singh, S. & Guestrin, C. (2016). Why should I trust you? Explaining the predictions of any classifier. In _Proceedings of the 22nd ACM SIGKDD international conference on knowledge discovery and data mining_ (S. 1135–1144). doi: https://doi.org/10.1145/2939672.2939778

## Usage

* Install packages from `requirements.txt`
* Run notebook `lime-demo.ipynb`
* The demo contains the following steps
  - load a subset of the MNIST dataset
  - train an SVM classifier
  - create LIME visualizations of interesting misclassifications

## Publications

Halbrügge, M & Jungeblut, T (2025). Beyond Heatmaps: Evaluating LIME Visualizations for Human Understandability. In _KogWis 2025. Biannual Conference of the German Cognitive Science Society_, Bochum, Germany

Halbrügge, M (2025). KI-Entscheidungen visualisieren und erklären mit LIME. Talk at _KI@HSBI 2025 - Zukunft im Fokus_, Bielefeld, Germany

## Data

This repository contains a small random sample from the MNIST dataset of handwritten digits.

LeCun, Y, Cortes, C & Burges, CJ (2010). MNIST handwritten digit database. Download: https://huggingface.co/datasets/ylecun/mnist

## License

This code is distributed under a BSD 2-clause license.

The application of LIME is inspired by Ribeiro's official tutorial at https://github.com/marcotcr/lime/blob/master/doc/notebooks/Tutorial%20-%20MNIST%20and%20RF.ipynb (BSD 2-clause)

The image example grid is based on code from https://www.kaggle.com/code/gainknowledge/mnist-scikit-learn-tutorial (Apache 2.0 license)

