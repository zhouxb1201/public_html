<template>
  <div class="goods-add bg-f8">
    <Navbar>
      <van-icon
        slot="slotRight"
        name="v-icon-search"
        @click="$router.push('/search?type=goods&search_type=add')"
      />
    </Navbar>
    <CategoryView :view-style="viewHeight">
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
      <div slot="content">
        <List
          v-model="loading"
          :finished="finished"
          :error.sync="error"
          :is-empty="isListEmpty"
          :empty="{message: '没有相关商品'}"
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
    </CategoryView>
    <div class="search-btn" v-if="$store.state.isWeixin">
      <van-icon
        name="v-icon-search"
        @click="$router.push('/search?type=goods&search_type=add')"
      />
    </div>
  </div>
</template>

<script>
import CategoryView from "@/components/CategoryView";
import GoodsPanelGroup from "./component/GoodsPanelGroup";
import { list } from "@/mixins";
import { GET_STOREGOODSCATEGORY, GET_STOREGOODSLIST } from "@/api/goods";
export default {
  name: "goods-add",
  data() {
    let height = this.$store.state.isWeixin ? "0" : "46";
    return {
      activeKey: 0,
      category_list: [],
      params: {
        category_id: ""
      },
      viewHeight: {
        height: `calc(100vh - ${height}px)`
      }
    };
  },
  mixins: [list],
  created() {
    this.loadData();
  },
  methods: {
    onChangeNav(index) {
      this.activeKey = index;
      this.params.category_id = this.category_list[index].category_id;
      this.loadList("init");
    },
    getCategory() {
      return new Promise((resolve, reject) => {
        GET_STOREGOODSCATEGORY({}, "add")
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
      GET_STOREGOODSLIST($this.params, "add")
        .then(({ data }) => {
          let list = data.goods_info || [];
          list.forEach(e => {
            e.operate = [{ text: "添加", type: "Add" }];
          });
          this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          this.loadError();
        });
    },
    btnOperate({ type }) {
      this.loadList("init");
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

.search-btn {
  position: fixed;
  background-color: #fff;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  color: #606266;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 800;
  -webkit-box-shadow: 0 0 6px rgba(0, 0, 0, 0.12);
  box-shadow: 0 0 6px rgba(0, 0, 0, 0.12);
  cursor: pointer;
  z-index: 999;
  right: 15px;
  bottom: 74px;
}
</style>