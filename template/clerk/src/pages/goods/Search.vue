<template>
  <div class="goods-search bg-f8">
    <HeadSearch
      :disabled="false"
      showLeft
      show-action
      :placeholder="params.search_text"
      @rightAction="onSearch"
    />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{message: '没有相关商品',top:'46'}"
      @load="loadList"
    >
      <GoodsPanelGroup
        v-for="(item,index) in list"
        :key="index"
        :items="item"
        @btn-click="btnOperate"
      />
    </List>
  </div>
</template>

<script>
import HeadSearch from "@/components/HeadSearch";
import GoodsPanelGroup from "./component/GoodsPanelGroup";
import { list } from "@/mixins";
import { GET_STOREGOODSCATEGORY, GET_STOREGOODSLIST } from "@/api/goods";
export default {
  data() {
    return {
      params: {
        search_text: this.$route.query.search_text || ""
      }
    };
  },
  mixins: [list],
  created() {
    this.loadList();
  },
  methods: {
    onSearch(value) {
      // const search_text = value.trim();
      // if (!search_text) return this.$Toast("内容不能为空");
      this.params.search_text = value;
      this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      const { search_type, params_type } = this.$route.query;
      GET_STOREGOODSLIST($this.params, search_type)
        .then(({ data }) => {
          let list = data.goods_info || [];
          list.forEach(e => {
            if (search_type == "add") {
              e.operate = [{ text: "添加", type: "Add" }];
            } else {
              e.operate = [{ text: "编辑", type: "Edit" }];
              if (params_type == 1) {
                e.operate.push({ text: "下架", type: "Offline" });
              }
              if (params_type == 2) {
                e.operate.push(
                  { text: "上架", type: "Online" },
                  { text: "移除", type: "Del" }
                );
              }
            }
          });
          this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          this.loadError();
        });
    },
    btnOperate({ type, text, id }) {
      this.loadList("init");
    }
  },
  components: {
    HeadSearch,
    GoodsPanelGroup
  }
};
</script>

<style scoped>
</style>