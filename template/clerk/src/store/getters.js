const getters = {
  token: state => state.user.token,
  avatar: state => state.account.avatar,
  store_id: state => state.config.store_id,
  getToPath: state => state.config.toPath ? state.config.toPath : '/'
}

export default getters
