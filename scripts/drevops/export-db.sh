#!/usr/bin/env bash
##
# Export database.
#
# This is a router script to call relevant scripts based on type.

t=$(mktemp) && export -p >"$t" && set -a && . ./.env && if [ -f ./.env.local ]; then . ./.env.local; fi && set +a && . "$t" && rm "$t" && unset t

set -e
[ -n "${DREVOPS_DEBUG}" ] && set -x

# Name of the database docker image to use. Uncomment to use an image with
# a DB data loaded into it.
# @see https://github.com/drevops/mariadb-drupal-data to seed your DB image.
DREVOPS_DB_DOCKER_IMAGE="${DREVOPS_DB_DOCKER_IMAGE:-}"

# ------------------------------------------------------------------------------

# @formatter:off
note() { printf "       %s\n" "$1"; }
info() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[INFO] %s\033[0m\n" "$1" || printf "[INFO] %s\n" "$1"; }
pass() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "$1" || printf "[ OK ] %s\n" "$1"; }
fail() { [ -z "${TERM_NO_COLOR}" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "$1" || printf "[FAIL] %s\n" "$1"; }
# @formatter:on

info "Started database export."

if [ -z "${DREVOPS_DB_DOCKER_IMAGE}" ]; then
  # Export database as a file.
  # @todo Replace with 'docker compose exec'.
  docker compose exec -T cli ./scripts/drevops/export-db-file.sh "$@"
else
  # Export database as a Docker image.
  DREVOPS_DB_EXPORT_DOCKER_IMAGE="${DREVOPS_DB_DOCKER_IMAGE}" ./scripts/drevops/export-db-docker.sh "$@"

  # Deploy docker image.
  # @todo Move deployment into a separate script.
  if [ "${DREVOPS_EXPORT_DB_DOCKER_DEPLOY_PROCEED}" = "1" ]; then
    DREVOPS_DEPLOY_DOCKER_MAP=mariadb=${DREVOPS_DB_DOCKER_IMAGE} \
      ./scripts/drevops/deploy-docker.sh
  fi
fi

pass "Finished database export."
