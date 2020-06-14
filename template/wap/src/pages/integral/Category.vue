<template>
  <Layout ref="load" class="integral-category">
    <HeadSearch searchType="integralgoods" />
    <div class="category-view">
      <section class="category">
        <div class="content">
          <div class="item">
            <div class="item-child" v-for="(item , index) in items" :key="index">
              <div
                class="item-box e-handle"
                @click="toList(item.category_id,item.short_name ? item.short_name : item.category_name)"
              >
                <div class="images">
                  <img v-lazy="item.category_pic" :key="item.category_pic" pic-type="square" />
                </div>
                <div class="name">{{item.short_name ? item.short_name : item.category_name}}</div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import HeadSearch from "@/components/HeadSearch";
import { GET_CATEGORYLIST } from "@/api/integral";
export default sfc({
  name: "integral-category",
  data() {
    return {
      items: []
    };
  },
  activated() {
    GET_CATEGORYLIST()
      .then(res => {
        this.items = res.data;
        this.$refs.load.success();
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  },
  methods: {
    toList(id, name) {
      this.$router.push({
        path: "/integral/goods/list",
        query: {
          category_id: id,
          text: name
        }
      });
    }
  },
  components: {
    HeadSearch
  }
});
</script>

<style scoped>
.integral-category {
  height: calc(100vh - 50px);
}
.integral-category >>> .category-view {
  width: 100%;
  height: calc(100% - 46px);
  -webkit-box-flex: 1;
  -webkit-flex: 1;
  -ms-flex: 1;
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
  position: relative;
}
.integral-category >>> .category {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
}
.integral-category >>> .category .content {
  height: 100%;
  background-color: #fff;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  position: relative;
}
.integral-category >>> .category .content .item {
  margin: 8px 4px 20px;
  overflow: hidden;
}
.integral-category >>> .category .content .item .item-child {
  width: 25%;
  float: left;
  text-align: center;
}
.integral-category >>> .category .content .item .item-child .item-box {
  margin: 4px;
}

.integral-category >>> .category .content .item .item-child .images {
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #f9f9f9;
}

.integral-category >>> .category .content .item .item-child .images img {
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #fff;
  border: none;
}

.integral-category >>> .category .content .item .item-child .name {
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  padding: 6px 4px 8px;
}
</style>