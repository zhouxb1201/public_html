<template>
  <CategoryView :top="viewTop" bottom="50">
    <div slot="nav" class="nav-group">
      <div
        class="item"
        :class="activeKey == index ? 'activeKey' : ''"
        v-for="(item,index) in category_list"
        :key="index"
        @click="onChangeNav(index)"
      >
        <span class="nav-item-text">{{item.short_name||item.category_name}}</span>
      </div>
    </div>
    <List
      slot="content"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{message: '没有相关商品'}"
      @load="loadList"
    >
      <GoodsPanelGroup v-for="(item,index) in list" :key="index" :items="item" :info="info" />
    </List>
  </CategoryView>
</template>

<script>
import CategoryView from "@/components/CategoryView";
import GoodsPanelGroup from "./GoodsPanelGroup";
import { GET_STOREGOODSCATEGORY, GET_STOREGOODSLIST } from "@/api/store";
import { list } from "@/mixins";
export default {
  data() {
    return {
      viewTop: "",
      activeKey: 0,
      category_list: [],
      params: {
        category_id: "",
        store_id: this.$route.params.id || ""
      }
    };
  },
  props: {
    info: Object
  },
  mounted() {
    this.viewTop = this.$el.offsetTop;
    this.loadData();
  },
  mixins: [list],
  methods: {
    onChangeNav(index) {
      this.activeKey = index;
      this.params.category_id = this.category_list[index].category_id;
      this.loadList("init");
    },
    getCategory() {
      return new Promise((resolve, reject) => {
        GET_STOREGOODSCATEGORY({
          store_id: this.$route.params.id
        })
          .then(({ data }) => {
            this.category_list = data || [];
            resolve();
          })
          .catch(() => {});
      });
    },
    loadData(init) {
      this.getCategory().then(() => {
        this.params.category_id = this.category_list[this.activeKey]
          ? this.category_list[this.activeKey].category_id
          : "";
        this.loadList(init);
      });
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_STOREGOODSLIST($this.params)
        .then(({ data }) => {
          let list = data.goods_list || [];
          this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          this.loadError();
        });
    }
  },
  components: {
    CategoryView,
    GoodsPanelGroup
  }
};
</script>

<style scoped>
.nav-group .item {
  color: #323232;
  display: flex;
  align-items: center;
  height: 46px;
  justify-content: center;
  position: relative;
}

.nav-group .item.activeKey {
  color: #ff454e;
  background: #fff;
}

.nav-group .item.activeKey .nav-item-text::before {
  content: "";
  position: absolute;
  display: block;
  width: 2px;
  height: 16px;
  background: #ff454e;
  left: 0;
  top: 50%;
  margin-top: -8px;
}
</style>