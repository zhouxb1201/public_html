<template>
  <div class="store">
    <div class="head">请选择门店</div>
    <List v-model="loading" :finished="finished" :error.sync="error" @load="loadList">
      <div
        class="item e-handle"
        v-for="(item,index) in list"
        :key="index"
        @click="onSelect(item.store_id)"
      >
        <span>{{item.shop_name}}（{{item.store_name}}）</span>
      </div>
    </List>
  </div>
</template>

<script>
import { GET_STORELIST, SELECT_STORE } from "@/api/config";
import { list } from "@/mixins";
export default {
  name: "store",
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
      GET_STORELIST($this.params)
        .then(({ data }) => {
          let list = data.store_list ? data.store_list : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    onSelect(store_id) {
      this.$store.dispatch("selectStore", store_id).then(() => {
        const path =
          this.$store.getters.getToPath.indexOf("/gift/") !== -1
            ? this.$store.getters.getToPath
            : "/";
        this.$router.replace(path);
        this.$store.commit("removeToPath");
      });
    }
  }
};
</script>

<style scoped>
.head {
  text-align: center;
  font-size: 18px;
  padding: 40px 0 20px;
}

.store {
  background: #fff;
}

.item {
  margin: 15px 20px;
  background: #f8f8f8;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 30px 20px;
  font-size: 18px;
  font-weight: 800;
  border-radius: 4px;
  white-space: nowrap;
}
</style>

