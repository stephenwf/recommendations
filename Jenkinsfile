########################################################
# WARNING: This is not ready to use.
########################################################
elifePipeline {
    stage 'Checkout'
    checkout scm
    def commit = elifeGitRevision()

    stage 'Project tests'
    lock('search--ci') {
        builderDeployRevision 'recommendations--ci', commit
        builderProjectTests 'recommendations--ci', '/srv/recommendations', ['/srv/recommendations/build/phpunit.xml']
    }

    elifeMainlineOnly {
        stage 'End2end tests'
        elifeEnd2EndTest({
            builderDeployRevision 'recommendations--end2end', commit
            builderSmokeTests 'recommendations--end2end', '/srv/recommendations'
        }, 'two')

        stage 'Approval'
        elifeGitMoveToBranch commit, 'approved'

        stage 'Not production yet'
        elifeGitMoveToBranch commit, 'master'
    }
}
