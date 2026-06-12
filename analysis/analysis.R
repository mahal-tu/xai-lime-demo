library(psych)
library(ggplot2)
library(lme4)
library(dplyr)
library(boot)
library(heplots)

###################################################################################################
# helpers for bootstrapped confidence intervals
meanCI <- function(x, d) { return(boot.ci(boot(x[d], function(d, i) mean(d[i]), R=10000), type="basic")$basic[4:5]) }
bootCI <- function(fn, d) {
  trm <- aggregate(fn, data=d, mean)
  trci <- aggregate(fn, data=d, meanCI)
  trm$CL <- trci[[ncol(trci)]][,1]
  trm$CU <- trci[[ncol(trci)]][,2]
  return(trm)
}

trimmedCI <- function(fn, d) {
  trm <- aggregate(fn, data=d, mean, .2)
  trci <- aggregate(fn, data=d, function(x, d) {
    return(boot.ci(boot(x[d],
      function(d, i) mean(d[i], .2), R=10000), type="basic")$basic[4:5]) })
  trm$CL <- trci[[ncol(trci)]][,1]
  trm$CU <- trci[[ncol(trci)]][,2]
  return(trm)
}

###################################################################################################
# long form trials
trials_long <- read.csv("analysis/trials_long.csv", header=TRUE, stringsAsFactors=TRUE)
trials_long$trial_id <- as.factor(paste(trials_long$trial_type, trials_long$trial_id, sep="#"))
trials_long$time <- as.POSIXct(trials_long$time, format="%Y-%m-%d %H:%M:%S")
trials_long$leftright <- factor(trials_long$leftright, levels=c(1, 2), labels=c("left", "right"))
# compute time between trials
trials_long <- trials_long %>%
  group_by(sessid) %>%
  mutate(leftright_lag1 = lag(leftright, n = 1),
    time_diff = as.numeric(difftime(time, lag(time), units = "secs")),
    log_tdiff = log(time_diff),
    log_tdiff = ifelse(is.infinite(log_tdiff), NA, log_tdiff)
  ) %>%
  ungroup()
summary(trials_long)

# left/right per user to identify cheaters
lr_user <- aggregate((as.numeric(leftright)-1) ~ sessid, trials_long, mean)
colnames(lr_user) <- c("sessid", "leftright")
head(sort(lr_user$leftright), n=4)
tail(sort(lr_user$leftright), n=4)
# remove subjects with unusual high dependency between left/right and previous choice
vp_chi2 <- trials_long %>% group_by(sessid) %>% summarise(chi2 = chisq.test(table(leftright, leftright_lag1))$statistic)
# visual check of cheat detection logic
# ggplot(vp_chi2) + geom_histogram(aes(x=chi2), bins=11)
cheaters <- vp_chi2 %>% filter(chi2 > qchisq(0.9, df = 1)) %>% pull(sessid)
# remove subjects who were unusually fast
vp_time <- trials_long %>% group_by(sessid) %>% summarise(time = mean(time_diff, na.rm = TRUE))

# hardcoded: 2 seconds on average is just too fast
(cheaters <- c(cheaters, vp_time %>% filter(time < 2) %>% pull(sessid)))
trials_long <- trials_long[!trials_long$sessid %in% cheaters, ]


###################################################################################################
# Sessions
sessions <- read.csv("analysis/session.csv", header=TRUE, row.names=1)
# remove test trials as well (with empty referrer)
cheaters <- c(cheaters, row.names(sessions)[is.na(sessions$referrer) | sessions$referrer==""])
sessions <- sessions[!row.names(sessions) %in% cheaters, ]
sessions$vis_type <- as.factor(sessions$vis_type)
sessions$browser_lang <- as.factor(sessions$browser_lang)
sessions$referrer <- as.factor(sessions$referrer)
sessions$os <- as.factor(sessions$os)
sessions$sess_len_s_clean <- ifelse(sessions$sess_len_s > 0 & sessions$sess_len_s < 60*15, sessions$sess_len_s, 60*15)
summary(sessions)
median(sessions$sess_len_s, na.rm=TRUE)
quantile(sessions$sess_len_s, probs = c(.1, 0.25, 0.75, .9), na.rm = TRUE)/60

###################################################################################################
# Demographics
demograph <- read.csv("analysis/demographics.csv", header=TRUE, row.names=1)
demograph$age <- ifelse(demograph$age > 0 & demograph$age<100, demograph$age, NA)
demograph$sex <- ifelse(demograph$sex != "none", demograph$sex, NA)
demograph$sex <- as.factor(demograph$sex)
demograph$edu <- ifelse(demograph$edu != "empty", demograph$edu, NA)
demograph$edu <- as.factor(demograph$edu)
demograph <- demograph[!row.names(demograph) %in% cheaters, ]
summary(demograph)

###################################################################################################
# AI knowledge
knowledge <- read.csv("analysis/knowledge.csv", header=TRUE, row.names=1)
knowledge <- knowledge[!row.names(knowledge) %in% cheaters, ]
summary(knowledge)
psych::alpha(knowledge)
# compute score
knowledge$know_score <- rowSums(knowledge)
mean(knowledge$know_score, na.rm=TRUE)
sd(knowledge$know_score, na.rm=TRUE)

###################################################################################################
# LIME trials overview (wide)
trials <- read.csv("analysis/trials_wide.csv", header=TRUE, row.names=1)
trials <- trials[!row.names(trials) %in% cheaters, ]
summary(trials)
psych::alpha(trials)
n_trials <- ncol(trials)
# compute score
trials$lime_completed <- rowSums(!is.na(trials[, 1:n_trials]))
(dropouts <- row.names(trials[trials$lime_completed < n_trials/2, ]))
trials$lime_score_s <- rowSums(trials[, 1:n_trials], na.rm=TRUE)
trials$lime_score_s <- ifelse(trials$lime_completed >= n_trials/2, trials$lime_score_s, NA)
trials$lime_score_m <- rowMeans(trials[, 1:n_trials], na.rm=TRUE)
trials$lime_score_m <- ifelse(trials$lime_completed >= n_trials/2, trials$lime_score_m, NA)
summary(trials$lime_score_m)

###################################################################################################
# combine everything
full <- merge(knowledge, trials, by="row.names", all=TRUE)
rownames(full) <- full$Row.names
full$Row.names <- NULL
full <- merge(full, demograph, by="row.names", all=TRUE)
rownames(full) <- full$Row.names
full$Row.names <- NULL
full <- merge(full, sessions, by="row.names", all=TRUE)
rownames(full) <- full$Row.names
full$Row.names <- NULL
# remove dropouts
full <- full[!rownames(full) %in% dropouts, ]
str(full)

###################################################################################################
# knowledge
cor.test(full$know_score, full$lime_score_m, method="pearson", use="pairwise.complete.obs")

xtabs(~know_score, full)
# need 50% to pass the exam
full$mlexpert <- as.factor(ifelse(full$know_score >= 3.5, "expert", "novice"))
xtabs(~mlexpert, full)
aggregate(lime_score_m ~ mlexpert, full, mean)
etasq(lm(lime_score_m ~ mlexpert, full), anova=TRUE)

ggplot(bootCI(lime_score_m ~ mlexpert, full), aes(x=mlexpert, y=lime_score_m)) +
  geom_hline(yintercept=0.5, linetype="dotted") +
  geom_point() + geom_errorbar(aes(ymin=CL, ymax=CU), width=.2) +
  theme_bw() +
  labs(x="Machine Learning Knowledge", y="Target Classifier Detection")


###################################################################################################
# analyze general scores
cor(full$know_score, full$lime_score_m, method="pearson", use="pairwise.complete.obs")
cor(full$age, full$lime_score_m, method="pearson", use="pairwise.complete.obs")
cor(rank(full$edu), full$lime_score_m, method="pearson", use="pairwise.complete.obs")
aggregate(lime_score_m ~ edu, full, mean)
cor(full$sess_len_s, full$lime_score_m, method="pearson", use="pairwise.complete.obs")
aggregate(lime_score_m ~ vis_type, full, mean)

ggplot(full, aes(x=know_score, y=lime_score_m)) +
  geom_point() + geom_smooth(method="lm")

# overall distributions
#ggplot(full, aes(x=lime_completed)) + geom_histogram(bins=5)
ggplot(full, aes(x=lime_score_m)) + geom_histogram(bins=5)
ggplot(full, aes(x=know_score)) + geom_histogram(bins=5)
ggplot(full, aes(x=age)) + geom_histogram(bins=5)
ggplot(bootCI(lime_score_m ~ vis_type, full), aes(x=vis_type, y=lime_score_m)) +
  geom_point() + geom_errorbar(aes(ymin=CL, ymax=CU), width=.2)

# covariates should not have any significant effects
etasq(lm(lime_score_m ~ sex, full), anova=TRUE)
etasq(lm(lime_score_m ~ os, full), anova=TRUE)
etasq(lm(lime_score_m ~ browser_lang, full), anova=TRUE)
cor.test(full$age, full$lime_score_m, method="pearson", use="pairwise.complete.obs")
cor.test(rank(full$edu), full$lime_score_m, method="pearson", use="pairwise.complete.obs")
cor.test(full$know_score, full$lime_score_m, method="pearson", use="pairwise.complete.obs")

# group differences?
etasq(lm(lime_score_m ~ referrer, full), anova=TRUE)
ggplot(bootCI(lime_score_m ~ referrer, full), aes(x=referrer, y=lime_score_m)) +
  geom_point() + geom_errorbar(aes(ymin=CL, ymax=CU), width=.2)
etasq(lm(know_score ~ referrer, full), anova=TRUE)
etasq(lm(sess_len_s ~ referrer, full), anova=TRUE)
ggplot(bootCI(sess_len_s ~ referrer, full), aes(x=referrer, y=sess_len_s)) +
  geom_point() + geom_errorbar(aes(ymin=CL, ymax=CU), width=.2)
etasq(lm(age ~ referrer, full), anova=TRUE)
ggplot(bootCI(age ~ referrer, full), aes(x=referrer, y=age)) +
  geom_point() + geom_errorbar(aes(ymin=CL, ymax=CU), width=.2)


###################################################################################################
# Detailed LIME trials analysis (long format)

# remove dropouts
trials_long <- trials_long[!trials_long$sessid %in% dropouts, ]

# correctness analysis
bootCI(correct ~ 1, trials_long)

# create variables for whether the classifiers were correct in the trial
trials_long$c1hit <- ifelse(trials_long$trial_type %in% c("abab", "abba"), TRUE, FALSE)
trials_long$c2hit <- ifelse(trials_long$trial_type %in% c("abab", "baab"), TRUE, FALSE)
trials_long$c12_concor <- trials_long$c1hit == trials_long$c2hit

aggregate(correct ~ vis_type, trials_long, mean)
aggregate(correct ~ leftright, trials_long, mean)
aggregate(correct ~ c1hit + c2hit, trials_long, mean)
aggregate(correct ~ c1hit + c12_concor, trials_long, mean)

grdata <- bootCI(correct ~ c1hit + c2hit, trials_long)
grdata$trueclassifier <- ifelse(grdata$c1hit, "correct", "incorrect")
grdata$bogusclassifier <- ifelse(grdata$c2hit, "correct", "incorrect")
ggplot(grdata, aes(x=trueclassifier, y=correct, color=bogusclassifier, shape=bogusclassifier)) +
  geom_hline(yintercept=0.5, linetype="dotted") +
  geom_point(position=position_dodge(width=0.5)) +
  geom_errorbar(aes(ymin=CL, ymax=CU), width=.2, position=position_dodge(width=0.5)) +
  theme_bw() +
  labs(x="Real Classifier", color="Bogus Classifier", shape="Bogus Classifier", y="Target Classifier Detection")


# Check intercept - better than chance?
m0 <- glmer(correct ~ 1 + (1|sessid) + (1|session_order), family = binomial, trials_long)
summary(m0)
exp(fixef(m0)) # odds ratios

# design check: presentation order should not have an impact
m1 <- glmer(correct ~ leftright + (1|sessid) + (1|session_order), family = binomial, trials_long)
summary(m1)
exp(fixef(m1))

# masks vs. heatmaps
m2 <- glmer(correct ~ vis_type + (1|sessid) + (1|session_order), family = binomial, trials_long)
summary(m2)
exp(fixef(m2))

# correctness of real and bogus classifier
m3 <- glmer(correct ~ c1hit * c2hit + (1|sessid) + (1|session_order), family = binomial, trials_long)
summary(m3)
exp(fixef(m3))


###################################################################################################
# design checks: did randomization work?

# counts should be nicely balanced
ggplot(aggregate(sessid ~ trial_id, trials_long, length), aes(y=sessid, x=trial_id)) +
  geom_bar(stat="identity") +
  theme(axis.text.x = element_text(angle = 90))

# left/right should be nicely balanced
etasq(lm(as.numeric(leftright) ~ trial_id, trials_long), anova=TRUE)
lr <- aggregate((as.numeric(leftright)-1) ~ trial_id, trials_long, mean)
colnames(lr) <- c("trial_id", "leftright")
ggplot(lr, aes(x=trial_id, y=leftright)) +
  geom_hline(yintercept=0.5) +
  geom_bar(stat="identity") +
  theme(axis.text.x = element_text(angle = 90))
ggplot(lr, aes(x=leftright)) + geom_histogram(bins=21) + xlim(0, 1)

