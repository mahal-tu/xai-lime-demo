# Visualize and Explain AI systems with LIME

Based on Local Interpretable Model-agnostic Explanations (LIME)

Ribeiro, M. T., Singh, S. & Guestrin, C. (2016). Why should I trust you? Explaining the predictions of any classifier. In Proceedings of the 22nd ACM SIGKDD international conference on knowledge discovery and data mining (S. 1135–1144). doi: https://doi.org/10.1145/2939672.2939778

## Publications

Halbrügge, M & Jungeblut, T (2025). Beyond Heatmaps: Evaluating LIME Visualizations
for Human Understandability. In KogWis 2025. Biannual Conference of the German Cognitive Science Society, Bochum, Germany

Halbrügge, M (2025). KI-Entscheidungen visualisieren und erklären mit LIME. Talk at KI@HSBI 2025 - Zukunft im Fokus, Bielefeld, Germany

## Usage

* Install packages from `requirements.txt`
* Run notebook `lime-demo.ipynb`
* The demo contains the following steps
  - load a subset of the MNIST dataset
  - train an SVM classifier 
  - create LIME visualizations of interesting misclassifications

## Data

This repository contains a small random sample from the MNIST dataset of handwritten digits. 

LeCun, Y, Cortes, C & Burges, CJ (2010). MNIST handwritten digit database. Download: https://huggingface.co/datasets/ylecun/mnist

## License

This code is distributed under a BSD 2-clause license.

The application of LIME is inspired by Ribeiro's official tutorial at https://github.com/marcotcr/lime/blob/master/doc/notebooks/Tutorial%20-%20MNIST%20and%20RF.ipynb (BSD 2-clause)

The image example grid is based on code from https://www.kaggle.com/code/gainknowledge/mnist-scikit-learn-tutorial (Apache 2.0 license)