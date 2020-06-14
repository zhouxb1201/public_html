import { GET_JONSLIST } from "@/api/manage";
const manage = {
  state: {
    jobs: null
  },
  mutations: {
    setJobsList(state, data) {
      state.jobs = data
    }
  },
  actions: {
    // 获取岗位
    getJobsList(context) {
      return new Promise((resolve, reject) => {
        if (!context.state.jobs) {
          GET_JONSLIST().then(({ data }) => {
            const jobs = []
            data.forEach(e => {
              jobs.push({
                id: e.jobs_id,
                text: e.jobs_name
              })
            });
            context.commit('setJobsList', jobs)
            resolve(jobs)
          }).catch(error => {
            reject(error)
          })
        } else {
          resolve(context.state.jobs)
        }

      })
    }
  }
}

export default manage
