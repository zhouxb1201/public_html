<template>
  <div class="store bg-f8">
    <Navbar/>
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell
        :title="item.assistant_name+ '（'+item.jobs_name+'）'"
        v-for="(item,index) in list"
        :key="index"
      />
    </List>
    <div class="fixed-foot-btn-group">
      <van-button size="normal" type="danger" round block @click="onPost('add')">新增店员</van-button>
    </div>
  </div>
</template>

<script>
import { GET_CLERKLIST } from "@/api/manage";
import { list } from "@/mixins";
import { _encode } from "@/utils/base64";
export default {
  name: "manage-list",
  data() {
    return {};
  },
  mixins: [list],
  created() {
    this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_CLERKLIST($this.params)
        .then(({ data }) => {
          let list = data.assistant_list ? data.assistant_list : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    onPost(action, index) {
      if (action == "edit") {
        const {
          assistant_id,
          assistant_name,
          assistant_tel,
          jobs_id,
          jobs_name,
          status
        } = this.list[index];
        const info = {};
        info.assistant_id = assistant_id;
        info.assistant_name = assistant_name;
        info.assistant_tel = assistant_tel;
        info.jobs_id = jobs_id;
        info.status = status;
        this.$router.push({
          name: "manage-post",
          query: { info: _encode(JSON.stringify(info)) },
          hash: "#edit"
        });
      } else {
        this.$router.push("/manage/post");
      }
    }
  }
};
</script>

<style scoped>
.list {
  padding-bottom: 80px;
}
</style>
